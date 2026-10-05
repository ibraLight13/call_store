<?php

use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Support\TaxonomyRepository;
use App\Domain\Discovery\Cache\FeedCache;
use App\Models\User;
use App\Support\PinPresenter;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    Cache::forgetDriver('array');
    $this->seed(CatalogSeeder::class);

    app(TaxonomyRepository::class)->all(); // cold: computed + stored
    $warm = app(TaxonomyRepository::class)->all()['scripts']->first();

    expect($warm)->toBeInstanceOf(Script::class);
});

it('renders the home feed as a single masonry grid of gallery cards', function () {
    galleryPiece();
    FeedCache::flush();

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'class="pin-grid"'))->toBe(1);
    expect($html)->toContain('class="wall-label"')->not->toContain('class="pcard"');
});

it('tells visitors what to expect when the feed is empty', function () {
    FeedCache::flush();

    $this->get('/')->assertOk()->assertSee(__('shell.empty_feed_title'));
});

it('shows a script ribbon that links each script, set in its own face, into explore', function () {
    $this->seed(CatalogSeeder::class);
    $script = Script::query()->orderBy('sort')->firstOrFail();

    $this->get('/')
        ->assertOk()
        ->assertSee('class="ribbon"', false)
        ->assertSee(route('explore', ['script' => $script->id]), false)
        ->assertSee($script->name_ar);
});

it('features the newest piece in the home hero', function () {
    galleryPiece(['text' => 'إِنَّ مَعَ العُسرِ يُسرًا', 'normalized_text' => 'ان مع العسر يسرا']);
    FeedCache::flush();

    $this->get('/')->assertOk()->assertSee('class="hero-feature"', false);
});

it('renders the home page with no featured piece when the catalog is empty', function () {
    FeedCache::flush();

    $this->get('/')
        ->assertOk()
        ->assertSee('لوحاتٌ من نور الحرف')
        ->assertDontSee('class="hero-feature"', false);
});

it('offers add-to-cart as the primary action and a similar commission as secondary', function () {
    $piece = galleryPiece();

    $this->get(route('pieces.show', $piece))
        ->assertOk()
        ->assertSee(__('commerce.add_to_cart'))
        ->assertSee(route('commissions.create', $piece->seller), false)
        ->assertSee('class="frame"', false);
});

it('makes a similar commission the primary action once a piece is sold', function () {
    $piece = galleryPiece();
    $piece->forceFill(['sold_at' => now()])->save();

    $this->get(route('pieces.show', $piece))
        ->assertOk()
        ->assertSee(__('commerce.sold'))
        ->assertDontSee(route('cart.add', $piece), false)
        ->assertSee('btn btn-block btn-primary" href="'.route('commissions.create', $piece->seller), false);
});

it('hides follow and request-similar from the seller on their own piece', function () {
    $piece = galleryPiece();

    $this->actingAs($piece->seller)
        ->get(route('pieces.show', $piece))
        ->assertOk()
        ->assertDontSee(route('commissions.create', $piece->seller), false)
        ->assertDontSee(__('shell.follow'));
});

it('keeps the skip link readable in both themes', function () {
    // White on the dark theme's light teal measured 2.24:1; on-primary flips with the theme.
    expect(file_get_contents(resource_path('css/app.css')))
        ->toMatch('/\.skip-link\{[^}]*background:var\(--primary\);color:var\(--on-primary\)/');
});

it('gives the empty feed one clear next action', function () {
    FeedCache::flush();

    $this->get('/')->assertOk()->assertSee('class="btn btn-dark" href="'.route('explore'), false);
});

it('labels the report fields and keeps heading levels in order on the piece page', function () {
    $piece = galleryPiece();
    $viewer = User::factory()->create();

    $html = $this->actingAs($viewer)->get(route('pieces.show', $piece))->assertOk()->getContent();

    expect($html)->toContain('<label for="report-reason"')
        ->toContain('<label for="report-note"')
        ->toContain('<h2>'.__('social.comments').'</h2>')
        ->not->toContain('<h3>');
});

it('groups the language switch and lets Escape close the account menu', function () {
    $this->get('/')
        ->assertSee('class="menu-lang" role="group"', false)
        ->assertSee("e.key === 'Escape'", false);
});

it('does not offer the seller to buy their own piece', function () {
    $piece = galleryPiece();

    $this->actingAs($piece->seller)
        ->get(route('pieces.show', $piece))
        ->assertOk()
        ->assertDontSee(route('cart.add', $piece), false);
});

it('shows the unread count on the account menu so mobile users see it too', function () {
    $user = User::factory()->create();
    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('<span class="avatar">'.mb_substr($user->name, 0, 1).'<span class="count">1</span></span>', false);
});

it('writes calligraphy letters behind the home hero, hidden from assistive tech', function () {
    galleryPiece();
    FeedCache::flush();

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toMatch('/<div class="hero">\s*<svg class="ink-pattern" aria-hidden="true"/');
});

it('puts the letter pattern on empty states and error pages', function () {
    FeedCache::flush();

    $this->get('/')->assertSee('<div class="empty-state">', false)->assertSee('class="ink-pattern"', false);
    $this->get('/no-such-page')->assertNotFound()->assertSee('class="ink-pattern"', false);
});

it('keeps piece pages to paper grain only when there is art to show', function () {
    $piece = galleryPiece();
    galleryPiece(['seller' => $piece->seller]);

    $this->get(route('pieces.show', $piece))->assertOk()->assertDontSee('class="ink-pattern"', false);
});

it('draws the pattern letters in every calligraphy style we can serve: naskh, ruqah, kufic, nastaliq', function () {
    $svg = view('partials.ink-pattern')->render();

    foreach (['Amiri', 'Aref Ruqaa', 'Reem Kufi', 'Gulzar'] as $face) {
        expect($svg)->toContain("font-family:'{$face}'");
    }
});

it('loads the nastaliq face subset to the pattern letters only', function () {
    $this->get('/')->assertSee('family=Gulzar&text=', false);
});

it('draws tashkeel as standalone marks beside bare letters', function () {
    $svg = view('partials.ink-pattern')->render();

    preg_match_all('/<text[^>]*>([^<]+)<\/text>/u', $svg, $letters);
    foreach ($letters[1] as $letter) {
        expect(preg_match('/[\x{064B}-\x{0652}]/u', $letter))->toBe(0, "harakah attached to {$letter}");
    }

    foreach (['fatha', 'kasra', 'damma', 'shadda', 'sukun', 'tanween'] as $mark) {
        expect($svg)->toContain("class=\"haraka haraka-{$mark}\"");
    }
});

it('keeps the nastaliq subset to the bare pattern letters', function () {
    $this->get('/')->assertSee('family=Gulzar&text=%D9%86%D8%B3%D9%82&display=swap', false);
});
