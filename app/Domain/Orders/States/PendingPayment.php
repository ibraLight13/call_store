<?php

namespace App\Domain\Orders\States;

final class PendingPayment extends OrderState
{
    public static function getMorphClass(): string
    {
        return 'pending_payment';
    }

    public function labelKey(): string
    {
        return 'order_state.pending_payment';
    }
}
