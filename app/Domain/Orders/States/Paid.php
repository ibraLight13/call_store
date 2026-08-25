<?php

namespace App\Domain\Orders\States;

final class Paid extends OrderState
{
    public static function getMorphClass(): string
    {
        return 'paid';
    }

    public function labelKey(): string
    {
        return 'order_state.paid';
    }
}
