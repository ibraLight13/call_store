<?php

namespace App\Domain\Orders\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RemoveFromCart
{
    public function handle(User $buyer, int $pieceId): void
    {
        DB::table('cart_items')
            ->where('user_id', $buyer->id)
            ->where('piece_id', $pieceId)
            ->delete();
    }
}
