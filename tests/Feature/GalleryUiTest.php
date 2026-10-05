<?php

use App\Domain\Catalog\Models\Piece;
use App\Domain\Discovery\Cache\FeedCache;
use App\Models\User;
use App\Support\PinPresenter;
use Database\Seeders\RoleSeeder;

function galleryPiece(array $overrides = []): Piece
{
    $seller = $overrides['seller'] ?? User::factory()->create();
    unset($overrides['seller']);

    return Piece::query()->create(array_merge([
        'seller_id' => $seller->id,
        'text' => 'سُبحانَ اللَّه',
        'normalized_text' => 'سبحان الله',
        'text_language' => 'ar',
        'listing_type' => 'ready',
        'price_minor' => 98_000,
        'state' => 'approved',
        'published_at' => now(),
    ], $overrides))->load(['seller', 'script', 'media']);
}

it('lets the OS pick the theme until the user chooses one', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)          // no server-forced data-theme
        ->assertSee('document.documentElement.dataset.theme', false);
});

it('keeps the danger color off primary buttons', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('.btn-primary{background:var(--primary)')
        ->not->toContain('.btn-primary{background:var(--danger)');
});

it('renders the top-bar shell with a mobile tab bar and no side rail', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('class="topnav"', false)
        ->assertSee('class="tabbar"', false)
        ->assertDontSee('class="rail"', false)
        ->assertSee(route('login'), false);
});

it('shows seller tools only to sellers, and logout to everyone signed in', function () {
    $this->seed(RoleSeeder::class);

    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $this->actingAs($seller)->get('/')
        ->assertSee(route('create'), false)
        ->assertSee('form="logout-form"', false);

    $this->actingAs($buyer)->get('/')
        ->assertDontSee(route('create'), false)
        ->assertSee('form="logout-form"', false);
});

it('presents a piece as a flat card array', function () {
    $pin = PinPresenter::present(galleryPiece(), 0);

    expect(array_keys($pin))->toEqualCanonicalizing([
        'aspect', 'bg', 'font', 'id', 'img', 'ink', 'isReady', 'price', 'scriptName',
        'sellerName', 'sellerUrl', 'size', 'sold', 'text', 'url', 'weight',
    ]);
    expect($pin['aspect'])->toBeIn(['3 / 4', '1 / 1', '4 / 3']);
    expect($pin['img'])->toBeNull();
});

it('renders a typeset card without a duplicate title when there is no image', function () {
    $html = view('partials.pin-card', ['pin' => PinPresenter::present(galleryPiece(), 0)])->render();

    expect($html)->toContain('class="typeset"')
        ->not->toContain('onerror=')
        ->not->toContain('class="wl-title"');
});

it('falls back to typeset art when a card image fails to load', function () {
    $pin = array_merge(PinPresenter::present(galleryPiece(), 0), ['img' => '/storage/missing.jpg']);
    $html = view('partials.pin-card', ['pin' => $pin])->render();

    expect($html)->toContain('onerror="this.hidden=true;this.nextElementSibling.hidden=false"')
        ->toMatch('/<span class="typeset"\s+hidden/')
        ->toContain('class="wl-title"');
});

it('labels sold and commission-only works honestly', function () {
    $soldPiece = galleryPiece();
    $soldPiece->forceFill(['sold_at' => now()])->save();
    $sold = PinPresenter::present($soldPiece, 0);
    $commission = PinPresenter::present(galleryPiece(['listing_type' => 'commission', 'price_minor' => null]), 1);

    expect($sold['sold'])->toBeTrue();
    expect($commission['isReady'])->toBeFalse();
    expect(view('partials.pin-card', ['pin' => $sold])->render())->toContain(__('commerce.sold'));
    expect(view('partials.pin-card', ['pin' => $commission])->render())->toContain(__('shell.t_commission'));
});

it('serves the taxonomy from a serializing cache store once warm', function () {
    // Production uses the database store, which serializes; the array store only does when asked.
    config(['cache.stores.array.serialize' => true]);
    Illuminate\Support\Facades\Cache::forgetDriver('array');
    $this->seed(Database\Seeders\CatalogSeeder::class);

    app(App\Domain\Catalog\Support\TaxonomyRepository::class)->all(); // cold: computed + stored
    $warm = app(App\Domain\Catalog\Support\TaxonomyRepository::class)->all()['scripts']->first();

    expect($warm)->toBeInstanceOf(App\Domain\Catalog\Models\Script::class);
});
