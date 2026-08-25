<?php

namespace App\Domain\Commissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $commission_id
 * @property int $seller_id
 * @property int $version
 * @property string $image_path
 * @property string|null $seller_note
 * @property Carbon $created_at
 */
final class CommissionProof extends Model
{
    public $timestamps = false;

    protected $fillable = ['commission_id', 'seller_id', 'version', 'image_path', 'seller_note'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'version' => 'integer'];
    }

    /** @return BelongsTo<Commission, $this> */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
