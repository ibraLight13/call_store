<?php

namespace App\Domain\Commissions\States;

final class BalancePaid extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'balance_paid';
    }

    public function labelKey(): string
    {
        return 'commission_state.balance_paid';
    }
}
