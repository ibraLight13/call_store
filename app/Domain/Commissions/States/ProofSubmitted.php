<?php

namespace App\Domain\Commissions\States;

final class ProofSubmitted extends CommissionState
{
    public static function getMorphClass(): string
    {
        return 'proof_submitted';
    }

    public function labelKey(): string
    {
        return 'commission_state.proof_submitted';
    }
}
