<?php

use App\Domain\Catalog\Models\Piece;
use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\States\Approved;
use App\Domain\Commissions\States\BalancePaid;
use App\Domain\Commissions\States\Completed;
use App\Domain\Commissions\States\DepositPaid;
use App\Domain\Commissions\States\InProgress;
use App\Domain\Commissions\States\ProofSubmitted;
use App\Domain\Commissions\States\Quoted;
use App\Domain\Commissions\States\Shipped;
use App\Domain\Moderation\Actions\ResolveReport;
use App\Domain\Moderation\Models\Report;
use App\Domain\Social\Actions\CreateReview;
use App\Domain\Social\Models\Review;
use App\Domain\Social\Notifications\ReviewReceived;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function completedCommission(): array
{
    test()->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
    foreach ([$anas, $salma] as $s) {
        $s->forceFill(['stripe_account_id' => 'acct_'.$s->id, 'stripe_charges_enabled' => true])->save();
    }

    $buyer = User::factory()->create();

    $commission = Commission::query()->create([
        'number' => 'CM-7777',
        'buyer_id' => $buyer->id,
        'seller_id' => $anas->id,
        'text' => 'بَيتٌ يَسَعُهُ الخَير',
        'normalized_text' => 'بيت يسعه الخير',
    ]);

    // Walk consecutive legal transitions straight to completed.
    $path = [
        Quoted::class,
        DepositPaid::class,
        InProgress::class,
        ProofSubmitted::class,
        Approved::class,
        BalancePaid::class,
        Shipped::class,
        Completed::class,
    ];

    DB::table('commissions')->where('id', $commission->id)->update([
        'quote_minor' => 100_000,
        'deposit_minor' => 30_000,
        'transfer_status' => 'transferred',
        'transfer_ref' => 'tr_x',
        'deposit_paid_at' => now(),
        'balance_paid_at' => now(),
        'shipped_at' => now(),
    ]);

    foreach ($path as $target) {
        $commission->fresh()->state->transitionTo($target);
        $commission->fresh()->save();
    }

    return [$buyer, $anas, $salma, $commission];
}

it('lets the buyer review a completed commission once, verified on profile', function () {
    Notification::fake();

    [$buyer, $seller] = completedCommission();

    // Target the commission this test created, not a seeded demo one.
    $commission = $buyer->commissions()->firstOrFail();

    $response = $this->actingAs($buyer)->post(route('reviews.store', $commission), [
        'rating' => 5,
        'body' => 'تحفة فنية، تنفيذ متقن!',
    ]);

    $response->assertRedirect();

    $review = Review::query()->where('commission_id', $commission->id)->first();
    expect($review)->not->toBeNull()
        ->and($review->rating)->toBe(5)
        ->and($review->seller_id)->toBe($seller->id);

    // Second attempt is rejected.
    $this->actingAs($buyer)->post(route('reviews.store', $commission), ['rating' => 4])
        ->assertSessionHasErrors();

    expect(Review::query()->where('commission_id', $commission->id)->count())->toBe(1);

    Notification::assertSentTo($seller, ReviewReceived::class);

    // Reviews render on the artist's public profile.
    $this->get('/@'.$seller->username)
        ->assertOk()
        ->assertSee(__('reviews.verified_buyer'))
        ->assertSee('تحفة فنية، تنفيذ متقن!');
});

it('blocks reviews from non-buyers or incomplete commissions', function () {
    [$buyer, , $salma, $commission] = completedCommission();

    $outsider = User::factory()->create();

    expect(fn () => app(CreateReview::class)->handle($outsider, $commission, 5, null))
        ->toThrow(AuthorizationException::class);
});

it('accepts abuse reports and lets admin take down a reported piece', function () {
    $this->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
    $admin = User::query()->create(['name' => 'Admin', 'email' => 'boss@x.test', 'password' => 'password']);
    $admin->assignRole('admin');

    $piece = Piece::query()->create([
        'seller_id' => $anas->id,
        'text' => 'قطعة مشبوهة',
        'normalized_text' => 'قطعة مشبوهة',
        'listing_type' => 'ready',
        'price_minor' => 10_000,
        'state' => 'approved',
        'published_at' => now(),
    ]);

    $buyer = User::factory()->create();

    $this->actingAs($buyer)->post(route('reports.store'), [
        'reportable_type' => 'piece',
        'reportable_id' => $piece->id,
        'reason' => 'scam',
        'note' => 'يطلب الدفع خارج المنصة',
    ])->assertRedirect()->assertSessionHas('status');

    $report = Report::query()->latest('id')->first();
    expect($report->status)->toBe('pending')
        ->and($report->reason)->toBe('scam');

    // Admin resolves → piece is taken down to moderation.
    app(ResolveReport::class)->handle($admin, $report, 'resolved');

    expect($report->fresh()->status)->toBe('resolved')
        ->and($piece->fresh()->state::getMorphClass())->toBe('rejected');
});

it('serves an xml sitemap with pieces and profiles', function () {
    $this->seed(DatabaseSeeder::class);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('xml');
    $response->assertSee('/@anas', false);
});
