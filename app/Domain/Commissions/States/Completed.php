<?php

namespace App\Domain\Commissions\States;

final class Completed extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'completed';
    }

    public function labelKey(): string
    {
        return 'commission_state.completed';
    }
}
