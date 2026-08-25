<?php

namespace App\Domain\Catalog\States;

final class PendingReview extends PieceState
{
    public static function getMorphClass(): string
    {
        return 'pending_review';
    }

    public function labelKey(): string
    {
        return 'piece_state.pending';
    }
}
