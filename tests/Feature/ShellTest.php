<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

it('renders the themed bilingual shell', function () {
    $response = $this->get('/');

    $response->assertOk();
    // Literal Arabic on purpose: if the lang file ever gets double-encoded
    // (mojibake), reading the key back would "match" itself. A literal pins it.
    $response->assertSee('لوحاتٌ من نور الحرف');
    $response->assertSee('<html lang="ar" dir="rtl"', false);
    $response->assertDontSee('Ù„ÙˆØ­'); // mojibake marker must never appear
});

it('switches locale to english on request', function () {
    $this->get('/?lang=en')
        ->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertSee(__('shell.home_title'));

    expect(app()->getLocale())->toBe('en');
});

it('seeds roles and demo users', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Role::query()->pluck('name')->toArray())
        ->toContain('admin')
        ->toContain('seller')
        ->toContain('buyer');

    $admin = User::whereEmail('admin@khatt.test')->first();
    expect($admin->hasRole('admin'))->toBeTrue();
});

it('registers a new seller with the seller role', function () {
    $this->seed(RoleSeeder::class);

    $response = $this->post('/register', [
        'name' => 'خطّاط جديد',
        'username' => 'new-seller',
        'email' => 'new@khatt.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'seller',
    ]);

    $response->assertRedirect(route('home'));

    $user = User::whereEmail('new@khatt.test')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('seller'))->toBeTrue();
    expect($user->username)->toBe('new-seller');
});

it('lets admins open the admin panel', function () {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::whereEmail('admin@khatt.test')->first())
        ->get('/admin')
        ->assertOk();
});

it('denies buyers the admin panel', function () {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::whereEmail('abeer@khatt.test')->first())
        ->get('/admin')
        ->assertForbidden();
});

it('lets sellers open their studio panel', function () {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::whereEmail('anas@khatt.test')->first())
        ->get('/studio')
        ->assertOk();
});

it('denies buyers the studio panel', function () {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::whereEmail('abeer@khatt.test')->first())
        ->get('/studio')
        ->assertForbidden();
});
