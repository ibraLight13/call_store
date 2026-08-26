<?php

namespace App\Livewire;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Discovery\Contracts\PieceSearcher;
use App\Domain\Discovery\Data\FeedFilters;
use App\Domain\Discovery\Queries\FeedQuery;
use App\Support\Money;
use App\Support\Palette;
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
            [$bg, $ink] = Palette::tint(count($rendered));

            $script = $piece->script;
            $scriptSlug = $script !== null ? $script->slug : 'naskh';
            $scriptName = '';
            if ($script !== null) {
                $localized = $script->{'name_'.app()->getLocale()};
                $scriptName = is_string($localized) ? $localized : '';
            }

            $cover = $piece->coverMedia();
            $imgUrl = $cover?->derivativeUrl(480);

            $isReady = $piece->isReady() && $piece->price_minor !== null;

            $pin = [
                'id' => $piece->id,
                'url' => route('pieces.show', $piece),
                'text' => $piece->text,
                'img' => $imgUrl,
                'bg' => $bg,
                'ink' => $ink,
                'font' => Palette::font($scriptSlug),
                'weight' => Palette::weight($scriptSlug),
                'size' => max(22, min(54, (int) (220 / max(6, mb_strlen($piece->text)) + 14))),
                'scriptName' => $scriptName,
                'sellerUrl' => $piece->seller->profileUrl(),
                'sellerInitial' => mb_substr($piece->seller->name, 0, 1),
                'sellerName' => $piece->seller->name,
                'isReady' => $isReady,
                'sold' => $piece->sold_at !== null,
                'price' => Money::format($piece->price_minor),
            ];

            ksort($pin);
            $rendered[] = $pin;
        }

        return $rendered;
    }
}
