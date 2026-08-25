<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\Declined;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeclineCommission
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    public function handle(User $actor, Commission $commission, string $reason): Commission
    {
        Gate::forUser($actor)->authorize('manageAsSeller', $commission);

        $state = $commission->state::getMorphClass();

        if (! in_array($state, ['requested', 'quoted'], true)) {
            throw new \DomainException(__('commissions.decline_not_allowed'));
        }

        if (trim($reason) === '') {
            throw new \InvalidArgumentException(__('commissions.decline_reason_required'));
        }

        return DB::transaction(function () use ($actor, $commission, $reason): Commission {
            $commission->state->transitionTo(Declined::class);
            $commission->cancelled_at = now();
            $commission->save();

            app(PostCommissionMessage::class)->handle($actor, $commission, __('commissions.declined_msg', ['reason' => trim($reason)]));

            $this->notify->handle($commission, 'buyer', 'declined');

            return $commission;
        });
    }
}
