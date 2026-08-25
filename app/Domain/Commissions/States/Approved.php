<?php

namespace App\Domain\Commissions\States;

final class Approved extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'approved';
    }

    public function labelKey(): string
    {
        return 'commission_state.approved';
    }
}
