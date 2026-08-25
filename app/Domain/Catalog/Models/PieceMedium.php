<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

final class PieceMedium extends Model
{
    protected $fillable = [
        'piece_id',
        'position',
        'original_disk',
        'original_path',
        'width',
        'height',
        'derivatives',
    ];

    protected function casts(): array
    {
        return [
            'derivatives' => 'array',
            'position' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /** @return BelongsTo<Piece, $this> */
    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }

    public function derivativeUrl(int $width): ?string
    {
        $path = $this->derivatives[$width] ?? null;

        return $path === null ? null : Storage::disk('public')->url($path);
    }
}
