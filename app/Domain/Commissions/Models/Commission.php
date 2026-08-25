<?php

namespace App\Domain\Commissions\Models;

use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Script;
use App\Domain\Commissions\States\CommissionState;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property string $number
 * @property int $buyer_id
 * @property int $seller_id
 * @property CommissionState $state
 * @property string $text
 * @property int|null $quote_minor
 * @property int|null $deposit_minor
 * @property string $currency
 * @property int $revision_count
 * @property Carbon|null $quoted_at
 * @property Carbon|null $deposit_paid_at
 * @property Carbon|null $balance_paid_at
 * @property-read User $buyer
 * @property-read User $seller
 */
final class Commission extends Model
{
    use HasStates;

    protected $fillable = [
        'number',
        'buyer_id',
        'seller_id',
        'text',
        'text_language',
        'script_id',
        'medium_id',
        'width_cm',
        'height_cm',
        'message',
        'budget_minor',
        'quote_minor',
        'deposit_minor',
        'currency',
        'deadline',
        'checkout_ref',
        'revision_count',
        'tracking_number',
        'quoted_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => CommissionState::class,
            'deadline' => 'date',
            'budget_minor' => 'integer',
            'quote_minor' => 'integer',
            'deposit_minor' => 'integer',
            'revision_count' => 'integer',
            'quoted_at' => 'datetime',
            'deposit_paid_at' => 'datetime',
            'balance_paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
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

    /** @return BelongsTo<Script, $this> */
    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    /** @return BelongsTo<Medium, $this> */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class);
    }

    /** @return HasMany<CommissionProof, $this> */
    public function proofs()
    {
        return $this->hasMany(CommissionProof::class)->orderByDesc('version');
    }

    /** @return HasMany<CommissionMessage, $this> */
    public function messages()
    {
        return $this->hasMany(CommissionMessage::class)->oldest();
    }
}
