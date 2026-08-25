<?php

namespace App\Domain\Catalog\States;

use App\Domain\Catalog\Models\Piece;
use Spatie\ModelStates\Exceptions\InvalidConfig;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Piece>
 */
abstract class PieceState extends State
{
    abstract public function labelKey(): string;

    /**
     * @throws InvalidConfig
     */
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, PendingReview::class)
            ->allowTransition(PendingReview::class, Approved::class)
            ->allowTransition(PendingReview::class, Rejected::class)
            ->allowTransition(Rejected::class, Draft::class);
    }
}
