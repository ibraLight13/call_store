<?php

namespace App\Domain\Social\Models;

use App\Domain\Catalog\Models\Piece;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_private
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
final class Board extends Model
{
    protected $fillable = ['user_id', 'name', 'description', 'is_private'];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Piece, $this> */
    public function pieces(): BelongsToMany
    {
        return $this->belongsToMany(Piece::class, 'board_piece')->withPivot('created_at');
    }
}
