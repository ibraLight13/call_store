<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\Completed;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class CompleteCommission
{
    public function handle(User $actor, Commission $commission): Commission
    {
        $isBuyer = (int) $commission->buyer_id === (int) $actor->id;
        $isAdmin = $actor->hasRole('admin');

        if (! $isBuyer && ! $isAdmin) {
            Gate::forUser($actor)->authorize('manageAsSeller', $commission);

            // Sellers may only nudge completion after shipping; buyer confirms.
            throw new \DomainException(__('commissions.complete_by_buyer_only'));
        }

        if ($commission->state::getMorphClass() !== 'shipped') {
            throw new \DomainException(__('commissions.complete_not_allowed'));
        }

        $commission->state->transitionTo(Completed::class);
        $commission->completed_at = now();
        $commission->save();

        return $commission;
    }
}
