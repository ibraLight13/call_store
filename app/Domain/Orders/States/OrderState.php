<?php

namespace App\Domain\Orders\States;

use App\Domain\Orders\Models\Order;
use Spatie\ModelStates\Exceptions\InvalidConfig;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Order>
 */
abstract class OrderState extends State
{
    abstract public function labelKey(): string;

    /**
     * @throws InvalidConfig
     */
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(PendingPayment::class)
            ->allowTransition(PendingPayment::class, Paid::class)
            ->allowTransition(PendingPayment::class, Cancelled::class)
            ->allowTransition(Paid::class, Completed::class)
            ->allowTransition(Paid::class, Refunded::class);
    }
}
