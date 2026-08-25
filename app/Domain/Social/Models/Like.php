<?php

namespace App\Domain\Social\Models;

use App\Domain\Catalog\Models\Piece;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Like extends Model
{
    public $timestamps = false;

    protected $table = 'piece_likes';

    protected $fillable = ['user_id', 'piece_id', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Piece, $this> */
    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }
}
