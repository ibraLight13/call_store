<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\RevisionRequested;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RequestCommissionRevision
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    public function handle(User $buyer, Commission $commission, string $note): Commission
    {
        Gate::forUser($buyer)->authorize('manageAsBuyer', $commission);

        if ($commission->state::getMorphClass() !== 'proof_submitted') {
            throw new \DomainException(__('commissions.revision_not_allowed'));
        }

        if (trim($note) === '') {
            throw new \InvalidArgumentException(__('commissions.revision_note_required'));
        }

        return DB::transaction(function () use ($buyer, $commission, $note): Commission {
            $commission->state->transitionTo(RevisionRequested::class);
            $commission->save();

            app(PostCommissionMessage::class)->handle($buyer, $commission, __('commissions.revision_msg', ['note' => trim($note)]));

            $this->notify->handle($commission, 'seller', 'revision_requested');

            return $commission;
        });
    }
}
