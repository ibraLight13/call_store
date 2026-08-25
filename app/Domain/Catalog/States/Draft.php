<?php

namespace App\Domain\Catalog\States;

final class Draft extends PieceState
{
    public static function getMorphClass(): string
    {
        return 'draft';
    }

    public function labelKey(): string
    {
        return 'piece_state.draft';
    }
}
