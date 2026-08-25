<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\Refunded;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Notifications\RefundProcessed;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

final class RefundOrder
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function handle(User $actor, Order $order): Order
    {
        Gate::forUser($actor)->authorize('refund', $order);

        if ($order->state::getMorphClass() !== 'paid') {
            throw new \DomainException(__('commerce.refund_not_allowed'));
        }

        return DB::transaction(function () use ($order): Order {
            // Reverse every transferred seller share first (money back to platform).
            foreach ($order->sellers as $group) {
                if ($group->transfer_status === 'transferred') {
                    $this->gateway->reverseTransfer($group);
                    $group->update(['transfer_status' => 'reversed']);
                }
            }

            $order->state->transitionTo(Refunded::class);
            $order->refunded_at = now();
            $order->save();

            // The handmade pieces go back on the market.
            foreach ($order->items as $item) {
                DB::table('pieces')
                    ->where('id', $item->piece_id)
                    ->whereNotNull('sold_at')
                    ->update(['sold_at' => null]);
            }

            Notification::send(
                User::query()->whereKey($order->buyer_id)->get(),
                new RefundProcessed($order),
            );

            foreach ($order->sellers as $group) {
                Notification::send(
                    User::query()->whereKey($group->seller_id)->get(),
                    new RefundProcessed($order),
                );
            }

            return $order;
        });
    }
}
