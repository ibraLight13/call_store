<?php

namespace App\Livewire;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Discovery\Contracts\PieceSearcher;
use App\Domain\Discovery\Data\FeedFilters;
use App\Domain\Discovery\Queries\FeedQuery;
use App\Support\PinPresenter;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Feed extends Component
{
    /** @var list<array<string, string|bool|int|null>> */
    public array $pins = [];

    #[Locked]
    public ?string $nextCursor = null;

    public bool $hasMore = false;

    public function mount(FeedQuery $feedQuery, PieceSearcher $searcher): void
    {
        $this->pins = $this->toPins($feedQuery->firstPage());

        $page = $searcher->search('', FeedFilters::fromRequestParams([]), perPage: 24);
        $this->hasMore = $page->hasMorePages();
        $this->nextCursor = $page->nextCursor()?->encode() ?? null;
    }

    public function loadMore(PieceSearcher $searcher): void
    {
        if ($this->nextCursor === null) {
            return;
        }

        $page = $searcher->search('', FeedFilters::fromRequestParams([]), perPage: 24, cursor: $this->nextCursor);

        foreach ($this->toPins($page->items()) as $pin) {
            $this->pins[] = $pin;
        }

        $this->hasMore = $page->hasMorePages();
        $this->nextCursor = $page->nextCursor()?->encode() ?? null;
    }

    /**
     * Render-time data only; models never cross the Livewire payload.
     *
     * @param  iterable<int, Piece>  $pieces
     * @return list<array<string, string|bool|int|null>>
     */
    private function toPins(iterable $pieces): array
    {
        $rendered = [];

        foreach ($pieces as $piece) {
            $rendered[] = PinPresenter::present($piece, count($rendered));
        }

        return $rendered;
    }
}
