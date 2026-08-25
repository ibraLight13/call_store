<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Contracts\PaymentGateway;
use Throwable;

final class FulfillTransfers
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function handle(Order $order): void
    {
        foreach ($order->sellers as $group) {
            if ($group->transfer_status !== 'pending') {
                continue;
            }

            try {
                $ref = $this->gateway->createTransfer($group);

                $group->update([
                    'transfer_status' => 'transferred',
                    'transfer_ref' => $ref,
                    'transferred_at' => now(),
                ]);
            } catch (Throwable) {
                // Keep pending: retried on next fulfilment pass / payout phase.
                $group->update(['transfer_status' => 'failed']);
            }
        }
    }
}
