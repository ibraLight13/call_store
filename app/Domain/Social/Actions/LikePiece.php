<?php

namespace App\Domain\Social\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Events\PieceLiked;
use App\Domain\Social\Models\Like;
use App\Models\User;

final class LikePiece
{
    public function handle(User $actor, Piece $piece): bool
    {
        $like = Like::query()->firstOrCreate([
            'user_id' => $actor->id,
            'piece_id' => $piece->id,
        ], ['created_at' => now()]);

        if ($like->wasRecentlyCreated) {
            PieceLiked::dispatch($piece);
        }

        return $like->wasRecentlyCreated;
    }
}
