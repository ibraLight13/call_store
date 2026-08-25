<?php

namespace App\Http\Controllers\Discovery;

use App\Domain\Catalog\Support\TaxonomyRepository;
use App\Domain\Discovery\Contracts\PieceSearcher;
use App\Domain\Discovery\Data\FeedFilters;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ExploreController extends Controller
{
    public function __construct(
        private readonly PieceSearcher $searcher,
        private readonly TaxonomyRepository $taxonomy,
    ) {}

    public function __invoke(Request $request): View
    {
        $filters = FeedFilters::fromRequestParams($request->query());
        $paginator = $this->searcher->search(
            query: $filters->q ?? '',
            filters: $filters,
            perPage: 24,
            cursor: $request->query('cursor'),
        );

        return view('pages.explore', [
            'paginator' => $paginator,
            'filters' => $filters,
            'taxonomy' => $this->taxonomy->all(),
        ]);
    }
}
