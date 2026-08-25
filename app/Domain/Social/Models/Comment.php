<?php

namespace App\Domain\Social\Models;

use App\Domain\Catalog\Models\Piece;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Comment extends Model
{
    protected $fillable = ['user_id', 'piece_id', 'parent_id', 'body'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Comment, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /** @return BelongsTo<Piece, $this> */
    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }
}
