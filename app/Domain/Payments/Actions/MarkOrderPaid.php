<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\Paid;
use App\Domain\Payments\Notifications\OrderPaid;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The single money-critical transition: cart → paid.
 * Idempotent on the gateway event id, so webhook replays are no-ops.
 */
final class MarkOrderPaid
{
    public function __construct(private readonly FulfillTransfers $fulfill) {}

    public function handle(
        string $gateway,
        string $eventId,
        string $checkoutRef,
        string $paymentRef,
    ): Order {
        $claimed = DB::table('processed_webhooks')->insertOrIgnore([
            'gateway' => $gateway,
            'event_id' => $eventId,
            'processed_at' => now(),
        ]);

        if ($claimed === 0) {
            // Replayed event: someone already advanced this exact webhook.
            $existing = Order::query()->where('checkout_ref', $checkoutRef)->first();

            if ($existing === null) {
                throw new \RuntimeException(__('commerce.order_not_found'));
            }

            return $existing;
        }

        return DB::transaction(function () use ($gateway, $checkoutRef, $paymentRef, $eventId): Order {
            $order = Order::query()
                ->where('checkout_ref', $checkoutRef)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                DB::table('processed_webhooks')->where('event_id', $eventId)->delete();

                throw new \RuntimeException(__('commerce.order_not_found'));
            }

            if ($order->state::getMorphClass() !== 'pending_payment') {
                return $order; // Already settled through another path.
            }

            $order->state->transitionTo(Paid::class);
            $order->gateway = $gateway;
            $order->payment_ref = $paymentRef;
            $order->paid_at = now();
            $order->save();

            foreach ($order->items as $item) {
                DB::table('pieces')
                    ->where('id', $item->piece_id)
                    ->whereNull('sold_at')
                    ->update(['sold_at' => now()]);
            }

            DB::table('cart_items')->where('user_id', $order->buyer_id)->delete();

            $this->fulfill->handle($order);

            Notification::send(
                User::query()->whereKey($order->buyer_id)->get(),
                new OrderPaid($order, 'buyer'),
            );

            foreach ($order->sellers as $group) {
                Notification::send(
                    User::query()->whereKey($group->seller_id)->get(),
                    new OrderPaid($order, 'seller'),
                );
            }

            return $order;
        });
    }
}
