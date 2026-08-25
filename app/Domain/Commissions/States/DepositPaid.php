<?php

namespace App\Domain\Commissions\States;

final class DepositPaid extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'deposit_paid';
    }

    public function labelKey(): string
    {
        return 'commission_state.deposit_paid';
    }
}
