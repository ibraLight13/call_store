<?php

namespace App\Domain\Commissions\States;

final class RevisionRequested extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'revision_requested';
    }

    public function labelKey(): string
    {
        return 'commission_state.revision_requested';
    }
}
