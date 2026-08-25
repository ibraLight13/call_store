<?php

namespace App\Domain\Commissions\States;

final class Quoted extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'quoted';
    }

    public function labelKey(): string
    {
        return 'commission_state.quoted';
    }
}
