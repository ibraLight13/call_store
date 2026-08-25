<?php

use App\Domain\Identity\Actions\FollowUser;
use App\Domain\Identity\Actions\UnfollowUser;
use App\Domain\Identity\Actions\UpdateProfile;
use App\Domain\Identity\Data\ProfileData;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('shows a public profile at its @username url', function () {
    $this->seed(DatabaseSeeder::class);

    $anas = User::whereUsername('anas')->first();

    $this->get('/@anas')
        ->assertOk()
        ->assertSee($anas->name)
        ->assertSee('@anas');
});

it('404s for unknown usernames', function () {
    $this->get('/@nobody-here')->assertNotFound();
});

it('lets a user update their own profile', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->put('/settings/profile', [
        'name' => 'الاسم الجديد',
        'username' => 'new-handle',
        'bio' => 'نبذة عن الخطّاط',
        'locale' => 'ar',
        'avatar' => UploadedFile::fake()->image('avatar.png', 800, 800),
    ]);

    $response->assertRedirect(route('settings.profile.edit'));

    $user->refresh();
    expect($user->name)->toBe('الاسم الجديد');
    expect($user->username)->toBe('new-handle');
    expect($user->bio)->toBe('نبذة عن الخطّاط');

    $stored = Storage::disk('public')->files('avatars');
    expect($stored)->toHaveCount(1);
});

it('forbids updating someone else profile through the action', function () {
    $actor = User::factory()->create();
    $victim = User::factory()->create();

    $data = new ProfileData(name: 'X', username: 'x1', bio: null, locale: null, avatar: null, cover: null);

    app(UpdateProfile::class)->handle($actor, $actor, $data);

    expect(fn () => app(UpdateProfile::class)->handle($actor, $victim, $data))
        ->toThrow(AuthorizationException::class);
});

it('rejects taken or invalid usernames on update', function () {
    [$a, $b] = User::factory()->count(2)->create();

    $this->actingAs($a)
        ->put('/settings/profile', ['name' => $a->name, 'username' => $b->username])
        ->assertSessionHasErrors('username');
});

it('follows and unfollows idempotently and forbids self-follow', function () {
    [$a, $b] = User::factory()->count(2)->create();

    app(FollowUser::class)->handle($a, $b);
    app(FollowUser::class)->handle($a, $b);

    expect($b->followers()->count())->toBe(1);
    expect($a->isFollowing($b))->toBeTrue();

    app(UnfollowUser::class)->handle($a, $b);

    expect($a->isFollowing($b))->toBeFalse();

    expect(fn () => app(FollowUser::class)->handle($a, $a))
        ->toThrow(DomainException::class);
});

it('persists locale preference for logged in users', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get('/?lang=en')->assertOk();

    expect($user->fresh()->locale)->toBe('en');

    $this->get('/')->assertOk();
    expect(app()->getLocale())->toBe('en');
});

it('keeps guest language choice in session only', function () {
    $this->get('/?lang=en')->assertOk();

    expect(User::query()->count())->toBe(0);
});

it('lists users in the filament admin panel', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::whereEmail('admin@khatt.test')->first();
    $buyer = User::whereEmail('abeer@khatt.test')->first();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.users.index'))
        ->assertOk()
        ->assertSee('@anas');

    $this->actingAs($buyer)
        ->get(route('filament.admin.resources.users.index'))
        ->assertForbidden();
});
