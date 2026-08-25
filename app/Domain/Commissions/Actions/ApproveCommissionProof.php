<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\Approved;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class ApproveCommissionProof
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    public function handle(User $buyer, Commission $commission): Commission
    {
        Gate::forUser($buyer)->authorize('manageAsBuyer', $commission);

        if ($commission->state::getMorphClass() !== 'proof_submitted') {
            throw new \DomainException(__('commissions.approve_not_allowed'));
        }

        $commission->state->transitionTo(Approved::class);
        $commission->save();

        $this->notify->handle($commission, 'seller', 'proof_approved');

        return $commission;
    }
}
