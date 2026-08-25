<?php

use App\Domain\Catalog\Actions\ApprovePiece;
use App\Domain\Catalog\Actions\CreatePiece;
use App\Domain\Catalog\Actions\PublishPiece;
use App\Domain\Catalog\Actions\RejectPiece;
use App\Domain\Catalog\Data\PieceData;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\States\Approved;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

function pieceData(array $overrides = [], array $photos = []): PieceData
{
    return new PieceData(
        text: $overrides['text'] ?? 'وَقُل رَّبِّ زِدني عِلمًا',
        textLanguage: 'ar',
        transliteration: null,
        scriptId: null,
        mediumId: null,
        themeId: null,
        colorId: null,
        widthCm: 50,
        heightCm: 70,
        listingType: 'ready',
        priceMinor: 145_000,
        currency: 'SAR',
        uniqueStock: true,
        description: null,
        photos: $photos,
    );
}

it('normalizes arabic text on creation', function () {
    $this->seed(RoleSeeder::class);

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $piece = app(CreatePiece::class)->handle($seller, pieceData());

    expect($piece->normalized_text)->toBe('وقل رب زدني علما');
    expect($piece->state::getMorphClass())->toBe('draft');
});

it('runs the full moderation lifecycle', function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);

    $seller = User::whereEmail('anas@khatt.test')->first();
    $admin = User::whereEmail('admin@khatt.test')->first();

    $data = pieceData(photos: [UploadedFile::fake()->image('piece.jpg', 1200, 900)]);
    $piece = app(CreatePiece::class)->handle($seller, $data);
    expect(Piece::approved()->whereKey($piece->id)->exists())->toBeFalse();

    app(PublishPiece::class)->handle($seller, $piece);
    expect($piece->fresh()->state::getMorphClass())->toBe('pending_review');

    $originals = Storage::disk('local')->files('pieces/'.$piece->id);
    expect(count($originals))->toBe(1);

    app(ApprovePiece::class)->handle($admin, $piece);

    $piece = $piece->fresh();
    expect($piece->state::getMorphClass())->toBe('approved');
    expect($piece->reviewed_by)->toBe($admin->id);
    expect($piece->published_at)->not->toBeNull();

    $derivatives = Storage::disk('public')->files('pieces/'.$piece->id);
    expect(count($derivatives))->toBe(2);

    $this->get('/')
        ->assertOk()
        ->assertSee('وَقُل رَّبِّ زِدني عِلمًا', false);
});

it('requires a reason to reject and records it', function () {
    $this->seed(DatabaseSeeder::class);

    $seller = User::whereEmail('anas@khatt.test')->first();
    $admin = User::whereEmail('admin@khatt.test')->first();

    $piece = app(CreatePiece::class)->handle($seller, pieceData());
    app(PublishPiece::class)->handle($seller, $piece);

    app(RejectPiece::class)->handle($admin, $piece, 'الصورة غير واضحة');

    $piece = $piece->fresh();
    expect($piece->state::getMorphClass())->toBe('rejected');
    expect($piece->rejection_reason)->toBe('الصورة غير واضحة');
});

it('blocks illegal transitions and unauthorized actors', function () {
    $seller = User::factory()->create();
    $buyer = User::factory()->create();

    $piece = new Piece(['text' => 'تجربة']);
    $piece->seller_id = $seller->id;
    $piece->save();

    expect(fn () => $piece->state->transitionTo(Approved::class))
        ->toThrow(TransitionNotFound::class);

    expect(fn () => app(PublishPiece::class)->handle($buyer, $piece))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(ApprovePiece::class)->handle($buyer, $piece))
        ->toThrow(AuthorizationException::class);
});

it('lets sellers submit through the http form with photos', function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);

    $seller = User::whereEmail('anas@khatt.test')->first();

    $response = $this->actingAs($seller)->post('/create', [
        'text' => 'رِزقٌ وَبَرَكة',
        'text_language' => 'ar',
        'listing_type' => 'ready',
        'price' => '960',
        'currency' => 'SAR',
        'unique_stock' => '1',
        'submit_for_review' => '1',
        'photos' => [
            UploadedFile::fake()->image('a.jpg', 1000, 800),
            UploadedFile::fake()->image('b.jpg', 800, 1000),
        ],
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $piece = Piece::query()
        ->where('seller_id', $seller->id)
        ->where('normalized_text', 'رزق وبركه')
        ->latest('id')
        ->first();

    expect($piece)->not->toBeNull();
    expect($piece->price_minor)->toBe(96_000);
    expect(Storage::disk('local')->allFiles('pieces'))->toHaveCount(2);
    expect($piece->media()->count())->toBe(2);
    expect($piece->state::getMorphClass())->toBe('pending_review');
});
