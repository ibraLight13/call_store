<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\BalancePaid;
use App\Domain\Commissions\States\DepositPaid;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Settles a commission capture (deposit or balance) through the idempotent
 * webhook ledger — the same replay-proof path as orders.
 */
final class PayCommissionCapture
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly NotifyCounterparty $notify,
    ) {}

    public function handle(User $buyer, Commission $commission, string $purpose, string $eventId, string $paymentRef): Commission
    {
        Gate::forUser($buyer)->authorize('manageAsBuyer', $commission);

        $claimed = DB::table('processed_webhooks')->insertOrIgnore([
            'gateway' => config('commerce.gateway', 'fake'),
            'event_id' => $eventId,
            'processed_at' => now(),
        ]);

        if ($claimed === 0) {
            return $commission->fresh() ?? $commission; // replay: already settled
        }

        return DB::transaction(function () use ($buyer, $commission, $purpose, $paymentRef, $eventId): Commission {
            if ((int) $commission->buyer_id !== (int) $buyer->id) {
                throw new AuthorizationException;
            }

            if ($purpose === 'deposit') {
                if ($commission->state::getMorphClass() !== 'quoted') {
                    DB::table('processed_webhooks')->where('event_id', $eventId)->delete();
                    throw new \DomainException(__('commissions.deposit_not_payable'));
                }

                $amount = (int) ($commission->deposit_minor ?? 0);

                if ($amount <= 0 || ! is_int($commission->quote_minor)) {
                    throw new \DomainException(__('commissions.deposit_not_payable'));
                }

                $commission->state->transitionTo(DepositPaid::class);
                $commission->deposit_paid_at = now();
                $commission->deposit_payment_ref = $paymentRef;
                $commission->save();

                $this->notify->handle($commission, 'seller', 'deposit_paid');

                return $commission;
            }

            if ($purpose === 'balance') {
                if ($commission->state::getMorphClass() !== 'approved') {
                    DB::table('processed_webhooks')->where('event_id', $eventId)->delete();
                    throw new \DomainException(__('commissions.balance_not_payable'));
                }

                $balance = max(0, (int) $commission->quote_minor - (int) $commission->deposit_minor);

                // Money moves to the seller ONLY now — post-approval by design.
                try {
                    $ref = $this->gateway->createCommissionTransfer($commission);
                    $commission->transfer_status = 'transferred';
                    $commission->transfer_ref = $ref;
                    $commission->transferred_at = now();
                } catch (\Throwable) {
                    $commission->transfer_status = 'failed';
                }

                $commission->state->transitionTo(BalancePaid::class);
                $commission->balance_paid_at = now();
                $commission->balance_payment_ref = $paymentRef;
                $commission->save();

                $this->notify->handle($commission, 'seller', 'balance_paid');

                return $commission;
            }

            throw new \InvalidArgumentException(__('commissions.invalid_purpose'));
        });
    }
}
