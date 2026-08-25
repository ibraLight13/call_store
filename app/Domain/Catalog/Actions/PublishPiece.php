<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\States\PendingReview;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class PublishPiece
{
    public function handle(User $seller, Piece $piece): Piece
    {
        Gate::forUser($seller)->authorize('publish', $piece);

        $piece->state->transitionTo(PendingReview::class);
        $piece->save();

        return $piece;
    }
}
