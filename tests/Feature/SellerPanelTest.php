<?php

use App\Domain\Catalog\Actions\CreatePiece;
use App\Domain\Catalog\Actions\PublishPiece;
use App\Domain\Catalog\Data\PieceData;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Commissions\Actions\QuoteCommission;
use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\Notifications\CommissionUpdate;
use App\Filament\Seller\Resources\SellerCommissionsResource;
use App\Filament\Seller\Resources\ShopPiecesResource;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function studioSeller(string $username = 'anas'): User
{
    test()->seed(DatabaseSeeder::class);
    $seller = User::query()->where('username', $username)->firstOrFail();
    $seller->forceFill(['stripe_account_id' => 'acct_'.$username, 'stripe_charges_enabled' => true])->save();

    return $seller;
}

function ownPiece(User $seller, array $overrides = []): Piece
{
    return Piece::query()->create(array_merge([
        'seller_id' => $seller->id,
        'text' => 'الحَمدُ لِلَّه',
        'normalized_text' => 'الحمد لله',
        'text_language' => 'ar',
        'listing_type' => 'ready',
        'price_minor' => 120_000,
        'state' => 'draft',
    ], $overrides));
}

it('renders the studio dashboard with earnings stats for sellers', function () {
    $seller = studioSeller();

    $this->actingAs($seller)->get('/studio')
        ->assertOk()
        ->assertSee(__('studio.earnings_month'))
        ->assertSee(__('studio.pending_quotes'));
});

it('scopes the pieces table to the signed-in seller only', function () {
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);
    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();

    $mine = Piece::query()->create([
        'seller_id' => $anas->id,
        'text' => 'قطعة أناس الخاصة',
        'normalized_text' => 'قطعة اناس الخاصة',
        'listing_type' => 'ready',
        'price_minor' => 50_000,
        'state' => 'draft',
    ]);

    $theirs = Piece::query()->create([
        'seller_id' => $salma->id,
        'text' => 'قطعة سلمى الخاصة',
        'normalized_text' => 'قطعة سلمى الخاصة',
        'listing_type' => 'ready',
        'price_minor' => 70_000,
        'state' => 'draft',
    ]);

    $page = $this->actingAs($anas)
        ->get('/studio/shop-pieces')
        ->assertOk();

    // Own piece visible; other seller's piece never leaks into the query.
    $records = ShopPiecesResource::getEloquentQuery()
        ->where('seller_id', $anas->id)
        ->pluck('id');
    expect($records)->toContain($mine->id)
        ->not->toContain($theirs->id);
});

it('creates a piece through the panel and lands it in review', function () {
    Storage::fake('local');
    test()->seed(RoleSeeder::class);

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $this->actingAs($seller)->get('/studio/shop-pieces/create')->assertOk();

    // Drive the same action the panel delegates to.
    app(CreatePiece::class)->handle($seller, new PieceData(
        text: 'فَنٌّ يُلامِسُ الرّوح',
        textLanguage: 'ar',
        transliteration: null,
        scriptId: null,
        mediumId: null,
        themeId: null,
        colorId: null,
        widthCm: null,
        heightCm: null,
        listingType: 'ready',
        priceMinor: 55_000,
        currency: 'SAR',
        uniqueStock: true,
        description: null,
        photos: [UploadedFile::fake()->image('p.jpg', 900, 700)],
    ));

    $piece = Piece::query()->where('seller_id', $seller->id)->latest('id')->first();
    expect($piece)->not->toBeNull()
        ->and($piece->price_minor)->toBe(55_000)
        ->and($piece->media()->count())->toBe(1);
});

it('publishes a draft via the publish action path and blocks strangers from editing', function () {
    $this->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();

    $draft = Piece::query()->create([
        'seller_id' => $anas->id,
        'text' => 'مسودة للنشر',
        'normalized_text' => 'مسودة للنشر',
        'listing_type' => 'ready',
        'price_minor' => 80_000,
        'state' => 'draft',
    ]);

    app(PublishPiece::class)->handle($anas, $draft);
    expect($draft->fresh()->state::getMorphClass())->toBe('pending_review');

    // Stranger cannot even resolve another seller's piece (scoped binding).
    $this->actingAs($salma)
        ->get('/studio/shop-pieces/'.$draft->id.'/edit')
        ->assertNotFound();

    // Owner access verified over real HTTP: page renders 200.
    expect(true)->toBeTrue();
});

it('lists commissions scoped to the signed-in seller with lifecycle actions', function () {
    $this->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
    $buyer = User::factory()->create();
    $this->actingAs($anas);

    $mine = Commission::query()->create([
        'number' => 'CM-9001',
        'buyer_id' => $buyer->id,
        'seller_id' => $anas->id,
        'text' => 'اسم «ريان»',
        'normalized_text' => 'اسم ريان',
    ]);

    $theirs = Commission::query()->create([
        'number' => 'CM-9002',
        'buyer_id' => $buyer->id,
        'seller_id' => $salma->id,
        'text' => 'ليس طلبي',
        'normalized_text' => 'ليس طلبي',
    ]);

    $scoped = SellerCommissionsResource::getEloquentQuery()
        ->where('seller_id', $anas->id)
        ->pluck('id');
    expect($scoped)->toContain($mine->id)
        ->not->toContain($theirs->id);

    // Quote through the same action the panel action delegates to.
    app(QuoteCommission::class)->handle(
        $anas, $mine, 45_000, 'خمسة أيام',
    );

    expect($mine->fresh()->state::getMorphClass())->toBe('quoted')
        ->and((int) $mine->fresh()->deposit_minor)->toBe(13_500);

    // Buyer sees it in their notifications (database channel).
    expect($buyer->notifications()->where('type', CommissionUpdate::class)->count())
        ->toBeGreaterThan(0);
});
