<?php

namespace App\Domain\Discovery\Contracts;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Discovery\Data\FeedFilters;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface PieceSearcher
{
    /**
     * Full-text search over normalized piece text combined with facet filters.
     * Engine swap (MySQL FULLTEXT today, Meilisearch later) stays behind this seam.
     *
     * @return CursorPaginator<int, Piece>
     */
    public function search(string $query, FeedFilters $filters, int $perPage = 24, ?string $cursor = null): CursorPaginator;
}
