<?php

use App\Domain\Catalog\Models\Piece;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\States\Refunded;
use App\Domain\Payments\Actions\MarkOrderPaid;
use App\Domain\Payments\Notifications\OrderPaid;
use App\Domain\Payments\Notifications\RefundProcessed;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

function buyablePiece(User $seller, array $overrides = []): Piece
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

function onboard(User $seller): void
{
    $seller->forceFill([
        'stripe_account_id' => 'acct_fake_'.$seller->id,
        'stripe_charges_enabled' => true,
    ])->save();
}

function placeTwoSellerOrder(): array
{
    test()->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
    onboard($anas);
    onboard($salma);

    $pieceA = buyablePiece($anas, ['price_minor' => 145_000, 'text' => 'نورٌ على الدرب', 'normalized_text' => 'نور على الدرب']);
    $pieceB = buyablePiece($salma, ['listing_type' => 'commission', 'price_minor' => null]);
    $pieceB2 = buyablePiece($salma, ['text' => 'قلمٌ لا ينقطع', 'normalized_text' => 'قلم لا ينقطع', 'price_minor' => 62_000]);

    $buyer = User::factory()->create();

    foreach ([$pieceA, $pieceB2] as $p) {
        test()->actingAs($buyer)->post(route('cart.add', $p))->assertRedirect();
    }

    // Commission-only pieces are not buyable.
    test()->actingAs($buyer)->post(route('cart.add', $pieceB))
        ->assertRedirect()
        ->assertSessionHas('error');

    return [$buyer, collect([$anas, $salma]), $pieceA, $pieceB2];
}

it('runs the full two-seller purchase with one payment and correct split', function () {
    Notification::fake();

    [$buyer, $sellers, $pieceA, $pieceB2] = placeTwoSellerOrder();
    [$anas, $salma] = $sellers;

    $response = $this->post(route('checkout.store'), [
        'recipient_name' => 'عبير م.',
        'phone' => '0555000111',
        'city' => 'الرياض',
        'street' => 'شارع الملك فهد',
        'save_address' => '1',
    ]);

    $order = Order::query()->latest('id')->first();

    // Checkout redirects to the gateway session URL.
    expect($order)->not->toBeNull();
    $response->assertRedirect($order->checkout_ref === null ? '' : route('checkout.fake_pay', ['order' => $order]));

    // Pay through the fake settlement path.
    $pay = $this->actingAs($buyer)->post(route('checkout.fake_pay', ['order' => $order]));
    $pay->assertRedirect(route('orders.show', $order));

    $order = $order->fresh();
    expect($order->state::getMorphClass())->toBe('paid');
    expect($order->payment_ref)->toBe('pay_fake_'.$order->id);

    // Split: 40 SAR flat shipping per seller group.
    expect($order->sellers()->count())->toBe(2);

    $groupAnas = $order->sellers()->where('seller_id', $anas->id)->first();
    $groupSalma = $order->sellers()->where('seller_id', $salma->id)->first();

    expect((int) $groupAnas->subtotal_minor)->toBe(145_000);
    expect((int) $groupSalma->subtotal_minor)->toBe(62_000);
    expect((int) $groupAnas->shipping_minor + (int) $groupSalma->shipping_minor)->toBe($order->shipping_minor);
    expect((int) $order->total_minor)->toBe(145_000 + 62_000 + 4_000 + 4_000);

    // Transfers fanned out to both sellers.
    expect($groupAnas->transfer_status)->toBe('transferred');
    expect($groupSalma->transfer_status)->toBe('transferred');
    expect($groupAnas->transfer_ref)->not->toBe($groupSalma->transfer_ref);

    // Pieces are sold; cart cleared.
    expect($pieceA->fresh()->sold_at)->not->toBeNull();
    expect($pieceB2->fresh()->sold_at)->not->toBeNull();
    expect(DB::table('cart_items')->where('user_id', $buyer->id)->count())->toBe(0);

    // One paid notification each: buyer once, each seller once.
    Notification::assertSentToTimes($buyer, OrderPaid::class, 1);
    Notification::assertSentTo([$anas, $salma], OrderPaid::class, 1);

    // Each seller sees only their own group on the fulfilment page.
    $anasView = $this->actingAs($anas)->get(route('seller.orders.index'))
        ->assertOk()
        ->assertSee((string) $order->id)
        ->assertSee('نورٌ على الدرب')
        ->assertDontSee('قلمٌ لا ينقطع');

    $salmaView = $this->actingAs($salma)->get(route('seller.orders.index'))
        ->assertOk()
        ->assertSee('قلمٌ لا ينقطع')
        ->assertDontSee('نورٌ على الدرب');

    // Seller ships their group.
    $this->actingAs($salma)
        ->post(route('seller.orders.ship', $groupSalma), ['tracking_number' => 'SA-12345'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($groupSalma->fresh()->shipped_at)->not->toBeNull();
    expect($groupSalma->fresh()->tracking_number)->toBe('SA-12345');

    // Sold pieces show the sold badge and reject re-adds.
    $this->get(route('pieces.show', $pieceA))->assertSee(__('commerce.sold'));
    $this->actingAs($buyer)->post(route('cart.add', $pieceA))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('is idempotent against webhook replays', function () {
    Notification::fake();

    [$buyer, $sellers] = placeTwoSellerOrder();

    $checkoutUrl = $this->post(route('checkout.store'), [
        'recipient_name' => 'ع',
        'phone' => '0555',
        'city' => 'جدة',
        'street' => 'طريق الملك',
    ])->headers->get('Location');

    $order = Order::query()->latest('id')->first();
    expect($checkoutUrl)->toContain('/checkout/'.$order->id);

    $markPaid = app(MarkOrderPaid::class);
    $eventId = 'evt_stripe_replay_1';

    $first = $markPaid->handle('stripe', $eventId, (string) $order->checkout_ref, 'cs_test_1');
    $replay = $markPaid->handle('stripe', $eventId, (string) $order->checkout_ref, 'cs_test_1');

    expect($replay->id)->toBe($first->id);
    expect($replay->state::getMorphClass())->toBe('paid');

    // Exactly one notification set despite the replay.
    Notification::assertSentToTimes($buyer, OrderPaid::class, 1);
    expect(DB::table('processed_webhooks')->where('event_id', $eventId)->count())->toBe(1);
});

it('refunds cleanly: transfers reversed and pieces relisted', function () {
    Notification::fake();
    $this->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
    onboard($anas);

    $piece = buyablePiece($anas, ['price_minor' => 90_000]);
    $buyer = User::factory()->create();

    $admin = User::factory()->create(['username' => 'boss-admin']);
    $this->seed(RoleSeeder::class);
    $admin->assignRole('admin');

    $this->actingAs($buyer)->post(route('cart.add', $piece));
    $this->post(route('checkout.store'), [
        'recipient_name' => 'ع',
        'phone' => '0555',
        'city' => 'دمشق',
        'street' => 'شارع بغداد',
    ]);

    $order = Order::query()->latest('id')->first();
    $this->actingAs($buyer)->post(route('checkout.fake_pay', $order));

    expect($piece->fresh()->sold_at)->not->toBeNull();

    // Buyer cannot refund.
    $this->actingAs($buyer)->post(route('orders.refund', $order))->assertForbidden();

    $this->actingAs($admin)->post(route('orders.refund', $order))
        ->assertRedirect()
        ->assertSessionHas('status');

    $order = $order->fresh();
    expect($order->state::getMorphClass())->toBe('refunded');
    expect($order->sellers()->first()->transfer_status)->toBe('reversed');
    expect($piece->fresh()->sold_at)->toBeNull(); // back on the market

    Notification::assertSentTo($buyer, RefundProcessed::class);
});

it('guards cart additions and illegal refund states', function () {
    $this->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
    $buyer = User::factory()->create();

    $draft = buyablePiece($anas, ['state' => 'draft']);
    $sold = buyablePiece($salma, ['sold_at' => now()]);
    $commissionOnly = buyablePiece($anas, ['listing_type' => 'commission', 'price_minor' => null]);

    foreach ([$draft, $sold, $commissionOnly] as $bad) {
        $this->actingAs($buyer)
            ->post(route('cart.add', $bad))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // Seller cannot buy their own piece.
    $own = buyablePiece($anas);
    $this->actingAs($anas)->post(route('cart.add', $own))
        ->assertRedirect()
        ->assertSessionHas('error');

    // Refund from pending_payment is an illegal transition.
    $pending = Order::query()->create([
        'buyer_id' => $buyer->id,
        'subtotal_minor' => 100,
        'shipping_minor' => 0,
        'total_minor' => 100,
        'address' => [],
        'placed_at' => now(),
    ]);

    expect(fn () => $pending->state->transitionTo(Refunded::class))
        ->toThrow(TransitionNotFound::class);
});
