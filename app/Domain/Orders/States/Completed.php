<?php

namespace App\Domain\Orders\States;

final class Completed extends OrderState
{
    public static function getMorphClass(): string
    {
        return 'completed';
    }

    public function labelKey(): string
    {
        return 'order_state.completed';
    }
}
