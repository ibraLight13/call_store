<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Events\PieceApproved as PieceApprovedEvent;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\States\Approved;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Support\Facades\Gate;

final class ApprovePiece
{
    public function __construct(private readonly Clock $clock) {}

    public function handle(User $moderator, Piece $piece): Piece
    {
        Gate::forUser($moderator)->authorize('moderate', $piece);

        $piece->state->transitionTo(Approved::class);
        $piece->reviewer()->associate($moderator);
        $piece->reviewed_at = $this->clock->now();
        $piece->published_at = $this->clock->now();
        $piece->save();

        PieceApprovedEvent::dispatch($piece);

        return $piece;
    }
}
