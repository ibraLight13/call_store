<?php

namespace App\Domain\Social\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Events\PieceSaved;
use App\Domain\Social\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SaveToBoard
{
    public function handle(User $actor, Board $board, Piece $piece): void
    {
        Gate::forUser($actor)->authorize('manage', $board);

        $sync = $board->pieces()->syncWithoutDetaching([$piece->getKey()]);
        $attached = $sync['attached'];

        if ($attached === []) {
            return; // Already saved to this board.
        }

        // A piece is "saved" once per user, no matter how many boards hold it.
        $alreadySaved = DB::table('piece_saves')
            ->where('user_id', $actor->id)
            ->where('piece_id', $piece->getKey())
            ->exists();

        if (! $alreadySaved) {
            DB::table('piece_saves')->insert([
                'user_id' => $actor->id,
                'piece_id' => $piece->getKey(),
                'created_at' => now(),
            ]);

            PieceSaved::dispatch($piece);
        }
    }
}
