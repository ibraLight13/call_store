<?php

namespace App\Domain\Social\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Social\Events\PieceUnsaved;
use App\Domain\Social\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class UnsaveFromBoard
{
    public function handle(User $actor, Board $board, Piece $piece): void
    {
        Gate::forUser($actor)->authorize('manage', $board);

        $board->pieces()->detach([$piece->getKey()]);

        // Still saved if any of the user's other boards holds the piece.
        $elsewhere = DB::table('board_piece')
            ->join('boards', 'boards.id', '=', 'board_piece.board_id')
            ->where('boards.user_id', $actor->id)
            ->where('board_piece.piece_id', $piece->getKey())
            ->where('board_piece.board_id', '!=', $board->id)
            ->exists();

        if (! $elsewhere) {
            $deleted = DB::table('piece_saves')
                ->where('user_id', $actor->id)
                ->where('piece_id', $piece->getKey())
                ->delete();

            if ($deleted > 0) {
                PieceUnsaved::dispatch($piece);
            }
        }
    }
}
