<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\Shipped;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ShipCommission
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    public function handle(User $seller, Commission $commission, ?string $trackingNumber = null): Commission
    {
        Gate::forUser($seller)->authorize('manageAsSeller', $commission);

        if ($commission->state::getMorphClass() !== 'balance_paid') {
            throw new \DomainException(__('commissions.ship_not_allowed'));
        }

        $commission->state->transitionTo(Shipped::class);
        $commission->shipped_at = now();
        $commission->tracking_number = $trackingNumber !== null && trim($trackingNumber) !== '' ? trim($trackingNumber) : null;
        $commission->save();

        $this->notify->handle($commission, 'buyer', 'shipped');

        return $commission;
    }
}
