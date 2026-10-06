<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Support\HomeContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $key
 * @property bool $is_visible
 * @property array<string, mixed>|null $content
 */
final class HomeSection extends Model
{
    protected $fillable = ['key', 'is_visible', 'content'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'content' => 'array'];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(HomeContent::CACHE_KEY);

        self::saved($flush);
        self::deleted($flush);
    }
}
