<?php

namespace App\Domain\Discovery\Queries;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Discovery\Cache\FeedCache;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FeedQuery
{
    public function __construct(private readonly FeedCache $cache) {}

    /**
     * First feed page, served from a cached ID list for anonymous speed;
     * invalidated by catalog events. Later pages always hit the keyset query.
     *
     * @return Collection<int, Piece>
     */
    public function firstPage(int $perPage = 24): Collection
    {
        $ids = $this->cache->rememberFirstPageIds($perPage);

        $byId = [];

        foreach ($this->baseQuery()->whereKey($ids)->get() as $piece) {
            $byId[$piece->id] = $piece;
        }

        $result = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $result[] = $byId[$id];
            }
        }

        return collect($result);
    }

    /** @return CursorPaginator<int, Piece> */
    public function paginate(int $perPage = 24, ?string $cursor = null): CursorPaginator
    {
        return $this->baseQuery()
            ->cursorPaginate(perPage: $perPage, cursor: $cursor);
    }

    /** @return Builder<Piece> */
    private function baseQuery()
    {
        return Piece::query()
            ->approved()
            ->with(['seller:id,name,username', 'script', 'color'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}
