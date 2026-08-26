<?php

namespace App\Domain\Discovery\Queries;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Discovery\Contracts\PieceSearcher;
use App\Domain\Discovery\Data\FeedFilters;
use App\Support\ArabicNormalizer;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class MysqlFulltextSearcher implements PieceSearcher
{
    public function search(string $query, FeedFilters $filters, int $perPage = 24, ?string $cursor = null): CursorPaginator
    {
        $normalized = ArabicNormalizer::normalize($query);

        return Piece::query()
            ->approved()
            ->with(['seller:id,name,username', 'script', 'color', 'media'])
            ->when($normalized !== '', function (Builder $q) use ($normalized): void {
                if (DB::connection()->getDriverName() === 'mysql') {
                    $q->whereRaw('MATCH (normalized_text) AGAINST (? IN NATURAL LANGUAGE MODE)', [$normalized]);
                } else {
                    // Test driver fallback: normalized substring match.
                    $q->where('normalized_text', 'like', "%$normalized%");
                }
            })
            ->when($filters->scriptId, fn (Builder $q, int $id) => $q->where('script_id', $id))
            ->when($filters->themeId, fn (Builder $q, int $id) => $q->where('theme_id', $id))
            ->when($filters->mediumId, fn (Builder $q, int $id) => $q->where('medium_id', $id))
            ->when($filters->colorId, fn (Builder $q, int $id) => $q->where('color_id', $id))
            ->when($filters->type, fn (Builder $q, string $type) => $q->where('listing_type', $type))
            ->when($filters->priceRange(), function (Builder $q, array $range): void {
                [$min, $max] = $range;
                $q->whereNotNull('price_minor')->where('price_minor', '>=', $min);
                if ($max !== null) {
                    $q->where('price_minor', '<=', $max);
                }
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate(perPage: $perPage, cursor: $cursor)
            ->withQueryString();
    }
}
