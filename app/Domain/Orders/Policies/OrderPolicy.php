<?php

namespace App\Domain\Orders\Policies;

use App\Domain\Orders\Models\Order;
use App\Models\User;

final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return (int) $order->buyer_id === (int) $user->id
            || $user->hasRole('admin')
            || $order->sellers()->where('seller_id', $user->id)->exists();
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }
}
