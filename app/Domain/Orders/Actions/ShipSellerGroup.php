<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Models\OrderSeller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ShipSellerGroup
{
    public function handle(User $seller, OrderSeller $group, ?string $trackingNumber = null): OrderSeller
    {
        if ((int) $group->seller_id !== (int) $seller->id && ! $seller->hasRole('admin')) {
            throw new AuthorizationException;
        }

        $group->update([
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber !== null && trim($trackingNumber) !== '' ? trim($trackingNumber) : null,
        ]);

        return $group->fresh() ?? $group;
    }
}
