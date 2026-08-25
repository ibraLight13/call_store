<?php

namespace App\Domain\Catalog\States;

final class Approved extends PieceState
{
    public static function getMorphClass(): string
    {
        return 'approved';
    }

    public function labelKey(): string
    {
        return 'piece_state.approved';
    }
}
