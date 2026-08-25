<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\InProgress;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class StartCommissionWork
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    public function handle(User $seller, Commission $commission): Commission
    {
        Gate::forUser($seller)->authorize('manageAsSeller', $commission);

        if ($commission->state::getMorphClass() !== 'deposit_paid') {
            throw new \DomainException(__('commissions.start_not_allowed'));
        }

        $commission->state->transitionTo(InProgress::class);
        $commission->save();

        $this->notify->handle($commission, 'buyer', 'started');

        return $commission;
    }
}
