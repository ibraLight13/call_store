<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Tag extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name_ar', 'name_en'];

    /** @return BelongsToMany<Piece, $this> */
    public function pieces(): BelongsToMany
    {
        return $this->belongsToMany(Piece::class);
    }
}
