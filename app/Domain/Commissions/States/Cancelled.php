<?php

namespace App\Domain\Commissions\States;

final class Cancelled extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'cancelled';
    }

    public function labelKey(): string
    {
        return 'commission_state.cancelled';
    }
}
