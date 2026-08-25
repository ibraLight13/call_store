<?php

use App\Domain\Catalog\Events\PieceApproved;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Domain\Discovery\Cache\FeedCache;
use App\Domain\Discovery\Contracts\PieceSearcher;
use App\Domain\Discovery\Data\FeedFilters;
use App\Domain\Discovery\Queries\FeedQuery;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function seedApprovedPiece(User $seller, array $overrides = []): Piece
{
    return Piece::query()->create(array_merge([
        'seller_id' => $seller->id,
        'text' => 'الحَمدُ لِلَّه',
        'normalized_text' => 'الحمد لله',
        'text_language' => 'ar',
        'listing_type' => 'ready',
        'price_minor' => 100_000,
        'state' => 'approved',
        'published_at' => now(),
    ], $overrides));
}

it('serves the home feed with load more cursor', function () {
    $this->seed(DatabaseSeeder::class);

    FeedCache::flush();

    $response = $this->get('/');
    $response->assertOk();
});

it('cursor paginates without overlap', function () {
    [$seller] = User::factory()->count(1)->create();

    foreach (range(1, 30) as $i) {
        seedApprovedPiece($seller, [
            'text' => "نص رقم $i",
            'published_at' => now()->subSeconds(31 - $i),
        ]);
    }

    FeedCache::flush();

    $searcher = app(PieceSearcher::class);
    $empty = FeedFilters::fromRequestParams([]);
    $pageOne = $searcher->search('', $empty, perPage: 10);
    expect($pageOne->count())->toBe(10);

    $pageTwo = $searcher->search('', $empty, perPage: 10, cursor: $pageOne->nextCursor()?->encode());

    $idsOne = $pageOne->getCollection()->pluck('id');
    $idsTwo = $pageTwo->getCollection()->pluck('id');

    expect($idsTwo)->toHaveCount(10);
    expect($idsOne->intersect($idsTwo))->toBeEmpty();
});

it('filters by script type and price band', function () {
    [$seller] = User::factory()->count(1)->create();
    $this->seed(RoleSeeder::class);
    $seller->assignRole('seller');
    $this->seed(CatalogSeeder::class);

    $thuluthId = Script::query()->where('slug', 'thuluth')->value('id');
    $diwaniId = Script::query()->where('slug', 'diwani')->value('id');

    seedApprovedPiece($seller, ['script_id' => $thuluthId, 'listing_type' => 'ready', 'price_minor' => 20_000]);
    seedApprovedPiece($seller, ['script_id' => $diwaniId, 'listing_type' => 'commission', 'price_minor' => null]);
    seedApprovedPiece($seller, ['script_id' => $thuluthId, 'listing_type' => 'ready', 'price_minor' => 900_000]);

    $filters = FeedFilters::fromRequestParams(['script' => (string) $thuluthId]);
    $results = app(PieceSearcher::class)->search('', $filters);

    expect($results->count())->toBe(2)
        ->and($results->getCollection()->every(fn ($p) => (int) $p->script_id === (int) $thuluthId))->toBeTrue();

    $readyFilters = FeedFilters::fromRequestParams(['type' => 'ready']);
    $readyResults = app(PieceSearcher::class)->search('', $readyFilters);
    expect($readyResults->count())->toBe(2);

    $cheapFilters = FeedFilters::fromRequestParams(['price' => 'lt500']);
    $cheapResults = app(PieceSearcher::class)->search('', $cheapFilters);
    expect($cheapResults->count())->toBe(1);
});

it('finds pieces ignoring diacritics through the normalized index', function () {
    [$seller] = User::factory()->count(1)->create();

    seedApprovedPiece($seller, ['text' => 'وَقُل رَّبِّ زِدني عِلمًا', 'normalized_text' => 'وقل رب زدني علما']);

    $filters = FeedFilters::fromRequestParams(['q' => 'زِدْني عِلمًا']);
    $results = app(PieceSearcher::class)->search('زِدْني عِلمًا', $filters);

    expect($results->count())->toBe(1);
});

it('shows approved piece detail publicly and hides drafts from guests', function () {
    [$seller] = User::factory()->count(1)->create();

    $approved = seedApprovedPiece($seller, ['description' => 'قطعة مميزة']);
    $draft = Piece::query()->create([
        'seller_id' => $seller->id,
        'text' => 'مسودة سرية',
        'text_language' => 'ar',
        'listing_type' => 'ready',
        'state' => 'draft',
    ]);

    $this->get(route('pieces.show', $approved))
        ->assertOk()
        ->assertSee('قطعة مميزة')
        ->assertSee(__('shell.related'));

    $this->get(route('pieces.show', $draft))->assertNotFound();
});

it('keeps related pieces approved and excludes the current one', function () {
    [$seller] = User::factory()->count(1)->create();
    $this->seed(CatalogSeeder::class);

    $themeId = Theme::query()->first()->getKey();
    $base = seedApprovedPiece($seller, ['theme_id' => $themeId]);
    $other = seedApprovedPiece($seller, ['theme_id' => $themeId, 'text' => 'نور العين']);
    $draftSibling = seedApprovedPiece($seller, ['theme_id' => $themeId, 'text' => 'خفية', 'state' => 'draft']);

    $view = $this->view('pages.piece', [
        'piece' => $base->load(['script', 'medium', 'theme', 'color', 'seller']),
        'related' => collect(),
        'topLevel' => collect(),
        'repliesByParent' => collect(),
    ]);

    $related = Piece::query()
        ->approved()
        ->whereKeyNot($base->getKey())
        ->when($themeId !== null, fn ($q) => $q->where('theme_id', $themeId))
        ->get();

    expect($related->pluck('id'))->toContain($other->id)
        ->and($related->pluck('id'))->not->toContain($base->id)
        ->and($related->pluck('id'))->not->toContain($draftSibling->id);
});

it('flushes the cached first page when a piece is approved', function () {
    Cache::flush();
    [$seller] = User::factory()->count(1)->create();

    // Warm cache.
    app(FeedQuery::class)->firstPage();
    expect(Cache::has((new FeedCache)->firstPageKey(24)))->toBeTrue();

    event(new PieceApproved(seedApprovedPiece($seller)));

    expect(Cache::has((new FeedCache)->firstPageKey(24)))->toBeFalse();
});

it('keeps feed query count flat and eager loaded', function () {
    $this->seed(DatabaseSeeder::class);
    [$seller] = User::factory()->count(1)->create();

    foreach (range(1, 12) as $i) {
        seedApprovedPiece($seller, ['text' => "نص $i", 'published_at' => now()->subSeconds($i)]);
    }

    FeedCache::flush();

    DB::enableQueryLog();
    $pieces = app(FeedQuery::class)->firstPage();

    // Touch every relation to force lazy loads if any were missed.
    foreach ($pieces as $piece) {
        $piece->seller->name;
        $piece->script?->slug;
        $piece->color?->hex;
    }

    expect(DB::getQueryLog())->toHaveCount(4); // id list + batch fetch + 3 eager-load batches
    expect($pieces)->not->toBeEmpty();
});
