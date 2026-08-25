<?php

namespace App\Domain\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $seller_id
 * @property int $subtotal_minor
 * @property int $shipping_minor
 * @property string $transfer_status
 * @property string|null $transfer_ref
 * @property Carbon|null $shipped_at
 * @property Carbon|null $transferred_at
 * @property-read User $seller
 * @property-read Order $order
 */
final class OrderSeller extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'seller_id',
        'subtotal_minor',
        'shipping_minor',
        'transfer_status',
        'transfer_ref',
        'transferred_at',
        'shipped_at',
        'tracking_number',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'transferred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Items belonging to this seller inside the parent order (composite scope).
     *
     * @return Builder<OrderItem>
     */
    public function items()
    {
        return OrderItem::query()
            ->where('order_id', $this->order_id)
            ->where('seller_id', $this->seller_id);
    }
}
