<?php

namespace App\Domain\Social\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Events\PieceUnliked;
use App\Domain\Social\Models\Like;
use App\Models\User;

final class UnlikePiece
{
    public function handle(User $actor, Piece $piece): bool
    {
        $deleted = Like::query()
            ->where('user_id', $actor->id)
            ->where('piece_id', $piece->id)
            ->delete();

        if ($deleted > 0) {
            PieceUnliked::dispatch($piece);
        }

        return $deleted > 0;
    }
}
