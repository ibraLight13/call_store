<?php

namespace App\Domain\Orders\Models;

use App\Domain\Orders\States\OrderState;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int $buyer_id
 * @property OrderState $state
 * @property int $subtotal_minor
 * @property int $shipping_minor
 * @property int $total_minor
 * @property string $currency
 * @property array<string, string|null> $address
 * @property string|null $gateway
 * @property string|null $checkout_ref
 * @property string|null $payment_ref
 * @property Carbon|null $paid_at
 * @property Carbon|null $refunded_at
 * @property-read User $buyer
 */
final class Order extends Model
{
    use HasStates;

    protected $fillable = [
        'buyer_id',
        'subtotal_minor',
        'shipping_minor',
        'total_minor',
        'currency',
        'address',
        'gateway',
        'checkout_ref',
        'payment_ref',
        'placed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'state' => OrderState::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return HasMany<OrderSeller, $this> */
    public function sellers()
    {
        return $this->hasMany(OrderSeller::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
