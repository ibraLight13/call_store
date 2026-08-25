<?php

namespace App\Domain\Discovery\Cache;

use App\Domain\Catalog\Models\Piece;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class FeedCache
{
    public const TAXONOMY_KEY = 'discovery:taxonomy';

    public function firstPageKey(int $perPage): string
    {
        return 'discovery:feed:first:'.app()->getLocale().':'.$perPage;
    }

    /**
     * Cached ordered ID list of the newest approved pieces.
     *
     * @return Collection<int, int>
     */
    public function rememberFirstPageIds(int $perPage): Collection
    {
        $cached = Cache::get($this->firstPageKey($perPage));

        if (is_array($cached)) {
            return collect($cached)
                ->filter(fn ($id): bool => is_int($id))
                ->values();
        }

        $ids = [];

        foreach (Piece::query()
            ->approved()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($perPage)
            ->get(['id']) as $row) {
            $ids[] = (int) $row->id;
        }

        Cache::put($this->firstPageKey($perPage), $ids, now()->addMinutes(10));

        return collect($ids);
    }

    public static function flush(): void
    {
        Cache::forget('discovery:feed:first:'.app()->getLocale().':24');
    }
}
