<?php

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\Piece;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'seller_id',
        'piece_id',
        'text',
        'price_minor',
        'currency',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Piece, $this> */
    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }
}
