<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\OrderSeller;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StartCheckout
{
    public function __construct(private readonly CartQuery $cart) {}

    /**
     * @param  array{recipient_name:string, phone:string, country?:string, city:string, district?:string|null, street:string, building?:string|null, postal_code?:string|null}  $address
     */
    public function handle(User $buyer, array $address): Order
    {
        $cart = $this->cart->for($buyer);

        if ($cart['groups']->isEmpty()) {
            throw new \DomainException(__('commerce.cart_empty'));
        }

        // Every seller in the cart must be capable of receiving transfers.
        foreach ($cart['groups'] as $group) {
            $seller = User::query()->find($group['seller']->id);

            if ($seller === null || ! $seller->stripe_charges_enabled) {
                throw new \DomainException(__('commerce.seller_not_connected', ['name' => $group['seller']->name]));
            }
        }

        return DB::transaction(function () use ($buyer, $address, $cart): Order {
            $order = Order::query()->create([
                'buyer_id' => $buyer->id,
                'subtotal_minor' => $cart['subtotal_minor'],
                'shipping_minor' => $cart['shipping_minor'],
                'total_minor' => $cart['total_minor'],
                'currency' => config('commerce.currency'),
                'address' => $address,
                'placed_at' => now(),
            ]);

            foreach ($cart['groups'] as $group) {
                OrderSeller::query()->create([
                    'order_id' => $order->id,
                    'seller_id' => (int) $group['seller']->id,
                    'subtotal_minor' => (int) $group['subtotal_minor'],
                    'shipping_minor' => (int) $group['shipping_minor'],
                ]);
            }

            foreach ($this->items($buyer) as $piece) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'seller_id' => (int) $piece->seller_id,
                    'piece_id' => (int) $piece->id,
                    'text' => $piece->text,
                    'price_minor' => (int) ($piece->price_minor ?? 0),
                    'currency' => config('commerce.currency'),
                ]);
            }

            $order->update([
                'checkout_ref' => 'chk_'.$order->id.'_'.bin2hex(random_bytes(6)),
            ]);

            return $order;
        });
    }

    /** @return Collection<int, Piece> */
    private function items(User $buyer): Collection
    {
        return Piece::query()
            ->whereIn('id', DB::table('cart_items')->where('user_id', $buyer->id)->pluck('piece_id'))
            ->get();
    }
}
