<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CartQuery
{
    /**
     * The buyer's cart, grouped by seller with per-group totals.
     *
     * @return array{
     *     groups: Collection<int, array{seller: User, pieces: Collection<int, Piece>, subtotal_minor: int, shipping_minor: int}>,
     *     subtotal_minor: int,
     *     shipping_minor: int,
     *     total_minor: int
     * }
     */
    public function for(User $buyer): array
    {
        $ids = DB::table('cart_items')->where('user_id', $buyer->id)->pluck('piece_id');

        $pieces = Piece::query()
            ->whereIn('id', $ids)
            ->approved()
            ->with(['seller:id,name,username', 'script', 'color'])
            ->get();

        $shippingConfig = config('commerce.shipping_flat_minor');
        $flatShipping = is_int($shippingConfig) ? $shippingConfig : 4_000;

        /** @var array<int, array{seller: User, pieces: Collection<int, Piece>, subtotal_minor: int, shipping_minor: int}> $groups */
        $groups = [];

        foreach ($pieces->groupBy(fn (Piece $p): int => (int) $p->seller_id) as $groupPieces) {
            $first = $groupPieces->first();

            if (! $first instanceof Piece) {
                continue;
            }

            $subtotal = 0;
            foreach ($groupPieces as $piece) {
                $subtotal += $piece->price_minor ?? 0;
            }

            $groups[] = [
                'seller' => $first->seller,
                'pieces' => $groupPieces->values(),
                'subtotal_minor' => $subtotal,
                'shipping_minor' => $flatShipping,
            ];
        }

        $subtotalMinor = 0;
        $shippingMinor = 0;

        foreach ($groups as $group) {
            $subtotalMinor += $group['subtotal_minor'];
            $shippingMinor += $group['shipping_minor'];
        }

        return [
            'groups' => collect($groups),
            'subtotal_minor' => $subtotalMinor,
            'shipping_minor' => $shippingMinor,
            'total_minor' => $subtotalMinor + $shippingMinor,
        ];
    }
}
