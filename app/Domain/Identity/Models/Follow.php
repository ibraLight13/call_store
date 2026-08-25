<?php

namespace App\Domain\Identity\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $follower_id
 * @property int $following_id
 * @property Carbon|null $created_at
 */
final class Follow extends Pivot
{
    public $timestamps = false;

    protected $table = 'follows';

    protected $fillable = ['follower_id', 'following_id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
