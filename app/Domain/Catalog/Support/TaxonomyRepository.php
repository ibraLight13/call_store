<?php

namespace App\Domain\Catalog\Support;

use App\Domain\Catalog\Models\Color;
use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Domain\Discovery\Cache\FeedCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class TaxonomyRepository
{
    /**
     * Immutable-ish catalog facets, cached forever and invalidated by model events.
     *
     * @return array{
     *     scripts: Collection<int, Script>,
     *     mediums: Collection<int, Medium>,
     *     themes: Collection<int, Theme>,
     *     colors: Collection<int, Color>
     * }
     */
    public function all(): array
    {
        /** @var array{scripts: Collection<int, Script>, mediums: Collection<int, Medium>, themes: Collection<int, Theme>, colors: Collection<int, Color>} $taxonomy */
        $taxonomy = Cache::rememberForever(FeedCache::TAXONOMY_KEY, function (): array {
            return [
                'scripts' => Script::query()->orderBy('sort')->get(),
                'mediums' => Medium::query()->orderBy('sort')->get(),
                'themes' => Theme::query()->orderBy('sort')->get(),
                'colors' => Color::query()->orderBy('sort')->get(),
            ];
        });

        return $taxonomy;
    }
}
