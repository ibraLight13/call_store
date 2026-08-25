<?php

namespace App\Domain\Social\Models;

use App\Domain\Commissions\Models\Commission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A verified review: written by the buyer of a COMPLETED commission,
 * one per commission — so every review on a profile is provably real.
 *
 * @property int $id
 * @property int $commission_id
 * @property int $buyer_id
 * @property int $seller_id
 * @property int $rating
 * @property string|null $body
 * @property Carbon|null $created_at
 */
final class Review extends Model
{
    protected $fillable = ['commission_id', 'buyer_id', 'seller_id', 'rating', 'body'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /** @return BelongsTo<Commission, $this> */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }
}
