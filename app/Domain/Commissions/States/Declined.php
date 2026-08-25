<?php

namespace App\Domain\Commissions\States;

final class Declined extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'declined';
    }

    public function labelKey(): string
    {
        return 'commission_state.declined';
    }
}
