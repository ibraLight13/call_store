<?php

namespace App\Domain\Orders\States;

final class Refunded extends OrderState
{
    public static function getMorphClass(): string
    {
        return 'refunded';
    }

    public function labelKey(): string
    {
        return 'order_state.refunded';
    }
}
