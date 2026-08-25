<?php

namespace App\Domain\Catalog\States;

final class Rejected extends PieceState
{
    public static function getMorphClass(): string
    {
        return 'rejected';
    }

    public function labelKey(): string
    {
        return 'piece_state.rejected';
    }
}
