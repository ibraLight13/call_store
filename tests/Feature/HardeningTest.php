<?php

use App\Domain\Catalog\Models\Piece;
use App\Domain\Moderation\Actions\ResolveReport;
use App\Domain\Moderation\Models\Report;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\RateLimiter;

it('sets security headers on every response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

it('exposes the status endpoint with database and storage checks', function () {
    $this->get('/status')
        ->assertOk()
        ->assertJson(['ok' => true, 'database' => true, 'storage' => true]);
});

it('locks out an account after five failed logins', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    foreach (range(1, 5) as $i) {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => "wrong-$i",
        ])->assertSessionHasErrors('email');
    }

    // Even the CORRECT password is now refused until the limiter decays.
    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertSessionHasErrors('email');

    // The limiter itself confirms lockout for this email+ip key.
    $key = 'login:'.mb_strtolower($user->email).'|127.0.0.1';
    expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue()
        ->and(auth()->check())->toBeFalse();

    // And a successful login is not registered in the session.
    expect(auth()->check())->toBeFalse();
});

it('enforces the policy matrix across modules', function () {
    $this->seed(DatabaseSeeder::class);

    [$anas, $salma] = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'seller'))->get();
    $buyer = User::factory()->create();
    $admin = User::query()->where('username', 'admin')->first();

    $piece = Piece::query()->create([
        'seller_id' => $anas->id,
        'text' => 'قطعة أناس',
        'normalized_text' => 'قطعة اناس',
        'listing_type' => 'ready',
        'price_minor' => 50_000,
        'state' => 'approved',
        'published_at' => now(),
    ]);

    // Piece publish: owner only.
    expect(Gate::forUser($anas)->allows('publish', $piece))->toBeTrue()
        ->and(Gate::forUser($salma)->allows('publish', $piece))->toBeFalse();

    // Order refund: admin only.
    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'subtotal_minor' => 100,
        'shipping_minor' => 0,
        'total_minor' => 100,
        'address' => [],
        'placed_at' => now(),
    ]);

    expect(Gate::forUser($admin)->allows('refund', $order))->toBeTrue()
        ->and(Gate::forUser($buyer)->allows('refund', $order))->toBeFalse()
        ->and(Gate::forUser($anas)->allows('refund', $order))->toBeFalse();

    // Report resolution: admin only via action guard.
    $report = Report::query()->create([
        'reporter_id' => $buyer->id,
        'reportable_type' => $piece::class,
        'reportable_id' => $piece->id,
        'reason' => 'other',
    ]);

    expect(fn () => app(ResolveReport::class)->handle($buyer, $report, 'dismissed'))
        ->toThrow(AuthorizationException::class);
});
