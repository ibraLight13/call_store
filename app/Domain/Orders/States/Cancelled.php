<?php

namespace App\Domain\Orders\States;

final class Cancelled extends OrderState
{
    public static function getMorphClass(): string
    {
        return 'cancelled';
    }

    public function labelKey(): string
    {
        return 'order_state.cancelled';
    }
}
