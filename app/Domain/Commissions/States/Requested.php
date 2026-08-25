<?php

namespace App\Domain\Commissions\States;

final class Requested extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'requested';
    }

    public function labelKey(): string
    {
        return 'commission_state.requested';
    }
}
