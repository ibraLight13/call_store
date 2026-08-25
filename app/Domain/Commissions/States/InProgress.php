<?php

namespace App\Domain\Commissions\States;

final class InProgress extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'in_progress';
    }

    public function labelKey(): string
    {
        return 'commission_state.in_progress';
    }
}
