<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AddToCart
{
    /**
     * @return bool true when newly added, false when it was already in the cart.
     */
    public function handle(User $buyer, Piece $piece): bool
    {
        if ((int) $piece->seller_id === (int) $buyer->id) {
            throw new \InvalidArgumentException(__('commerce.cannot_buy_own'));
        }

        if ($piece->state::getMorphClass() !== 'approved' || $piece->sold_at !== null || ! $piece->isReady()) {
            throw new \DomainException(__('commerce.piece_not_buyable'));
        }

        return DB::table('cart_items')->insertOrIgnore([
            'user_id' => $buyer->id,
            'piece_id' => $piece->id,
            'created_at' => now(),
        ]) > 0;
    }
}
