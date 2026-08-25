<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\States\PieceState;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int $seller_id
 * @property string $text
 * @property string|null $transliteration
 * @property string $text_language
 * @property PieceState $state
 * @property string $listing_type
 * @property int|null $price_minor
 * @property string $currency
 * @property Carbon|null $published_at
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property-read User $seller
 */
final class Piece extends Model
{
    use HasStates;

    protected $fillable = [
        'seller_id',
        'text',
        'normalized_text',
        'transliteration',
        'text_language',
        'script_id',
        'medium_id',
        'theme_id',
        'color_id',
        'width_cm',
        'height_cm',
        'listing_type',
        'price_minor',
        'currency',
        'unique_stock',
        'description',
        'state',
        'published_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'unique_stock' => 'boolean',
            'price_minor' => 'integer',
            'state' => PieceState::class,
        ];
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

    /** @return BelongsTo<Theme, $this> */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /** @return BelongsTo<Color, $this> */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /** @phpstan-return HasMany<PieceMedium, $this> */
    public function media()
    {
        return $this->hasMany(PieceMedium::class)->orderBy('position');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @param  Builder<Piece>  $query
     * @return Builder<Piece>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('state', 'approved');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isReady(): bool
    {
        return $this->listing_type === 'ready';
    }

    public function coverMedia(): ?PieceMedium
    {
        return $this->media->first();
    }
}
