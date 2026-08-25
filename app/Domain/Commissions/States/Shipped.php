<?php

namespace App\Domain\Commissions\States;

final class Shipped extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'shipped';
    }

    public function labelKey(): string
    {
        return 'commission_state.shipped';
    }
}
