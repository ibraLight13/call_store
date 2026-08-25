<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\Quoted;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class QuoteCommission
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    /**
     * Seller prices the request. Deposit is captured as a fixed share of the
     * quote and stored so both sides see one number.
     */
    public function handle(User $seller, Commission $commission, int $quoteMinor, ?string $note = null): Commission
    {
        Gate::forUser($seller)->authorize('manageAsSeller', $commission);

        if ($commission->state::getMorphClass() !== 'requested') {
            throw new \DomainException(__('commissions.not_quotable'));
        }

        if ($quoteMinor < 5_000) {
            throw new \InvalidArgumentException(__('commissions.quote_too_low'));
        }

        return DB::transaction(function () use ($seller, $commission, $quoteMinor, $note): Commission {
            $depositShareConfig = config('commissions.deposit_percent', 30);
            $depositShare = is_int($depositShareConfig) ? $depositShareConfig : 30;
            $deposit = (int) round($quoteMinor * $depositShare / 100);

            $commission->state->transitionTo(Quoted::class);
            $commission->quote_minor = $quoteMinor;
            $commission->deposit_minor = $deposit;
            $commission->quoted_at = now();
            $commission->save();

            if ($note !== null && trim($note) !== '') {
                app(PostCommissionMessage::class)->handle($seller, $commission, trim($note));
            }

            $this->notify->handle($commission, 'buyer', 'quoted');

            return $commission;
        });
    }
}
