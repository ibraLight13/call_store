<?php

namespace App\Domain\Commissions\States;

use App\Domain\Commissions\Models\Commission;
use Spatie\ModelStates\Exceptions\InvalidConfig;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * The commission lifecycle. The machine makes illegal money flows impossible:
 * work cannot start before the deposit clears, and the balance can only be
 * captured after the buyer approves a proof.
 *
 * @extends State<Commission>
 */
abstract class CommissionState extends State
{
    abstract public function labelKey(): string;

    /**
     * @throws InvalidConfig
     */
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Requested::class)
            ->allowTransition(Requested::class, Quoted::class)
            ->allowTransition(Requested::class, Declined::class)
            ->allowTransition(Quoted::class, DepositPaid::class)
            ->allowTransition(Quoted::class, Declined::class)
            ->allowTransition(Quoted::class, Cancelled::class)
            ->allowTransition(DepositPaid::class, InProgress::class)
            ->allowTransition(InProgress::class, ProofSubmitted::class)
            ->allowTransition(ProofSubmitted::class, RevisionRequested::class)
            ->allowTransition(RevisionRequested::class, ProofSubmitted::class)
            ->allowTransition(ProofSubmitted::class, Approved::class)
            ->allowTransition(Approved::class, BalancePaid::class)
            ->allowTransition(BalancePaid::class, Shipped::class)
            ->allowTransition(Shipped::class, Completed::class);
    }
}
