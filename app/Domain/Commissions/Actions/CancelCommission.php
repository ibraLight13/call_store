<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\Cancelled;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CancelCommission
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    public function handle(User $buyer, Commission $commission): Commission
    {
        Gate::forUser($buyer)->authorize('manageAsBuyer', $commission);

        if ($commission->state::getMorphClass() !== 'quoted') {
            throw new \DomainException(__('commissions.cancel_not_allowed'));
        }

        return DB::transaction(function () use ($buyer, $commission): Commission {
            $commission->state->transitionTo(Cancelled::class);
            $commission->cancelled_at = now();
            $commission->save();

            app(PostCommissionMessage::class)->handle($buyer, $commission, __('commissions.cancelled_msg'));

            $this->notify->handle($commission, 'seller', 'cancelled');

            return $commission;
        });
    }
}
