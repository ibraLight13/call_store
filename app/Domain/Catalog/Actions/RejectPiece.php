<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Events\PieceRejected as PieceRejectedEvent;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\States\Rejected;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final class RejectPiece
{
    public function __construct(private readonly Clock $clock) {}

    public function handle(User $moderator, Piece $piece, string $reason): Piece
    {
        Gate::forUser($moderator)->authorize('moderate', $piece);

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException(__('catalog.reject_reason_required'));
        }

        $piece->state->transitionTo(Rejected::class);
        $piece->rejection_reason = $reason;
        $piece->reviewer()->associate($moderator);
        $piece->reviewed_at = $this->clock->now();
        $piece->save();

        PieceRejectedEvent::dispatch($piece, $reason);

        return $piece;
    }
}
