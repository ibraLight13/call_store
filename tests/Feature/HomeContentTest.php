<?php

use App\Domain\Content\Models\HomeSection;
use App\Domain\Content\Support\HomeContent;
use App\Filament\Admin\Pages\ManageHomeContent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

function homeAdmin(): User
{
    test()->seed(RoleSeeder::class);
    $admin = User::factory()->create(['username' => 'home-admin']);
    $admin->assignRole('admin');

    return $admin;
}

it('falls back to the translated copy when nothing was edited', function () {
    $home = app(HomeContent::class)->forLocale('en');

    expect($home['hero']['title'])->toBe(__('shell.home_title', [], 'en'))
        ->and($home['how']['steps'])->toHaveCount(3)
        ->and($home['faq']['items'])->toHaveCount(4)
        ->and($home['faq']['items'][0]['question'])->toBe(__('shell.faq_1_q', [], 'en'));
});

it('only stores what differs from the defaults', function () {
    $content = app(HomeContent::class);
    $state = $content->editable();
    $content->save($state);

    expect(HomeSection::query()->whereNotNull('content')->count())->toBe(0);

    $state['cta']['title_en'] = 'Order today';
    $content->save($state);

    expect(HomeSection::query()->where('key', 'cta')->firstOrFail()->content)
        ->toBe(['title_en' => 'Order today']);
});

it('renders edited copy and hides switched-off sections on the home page', function () {
    $content = app(HomeContent::class);
    $state = $content->editable();
    $state['hero']['title_en'] = 'Ink that lasts';
    $state['hero']['title_accent_en'] = 'lasts';
    $state['how']['steps'] = [['title_ar' => 'خطوة', 'title_en' => 'Only step', 'body_ar' => '', 'body_en' => 'Just this']];
    $state['faq']['is_visible'] = false;
    $content->save($state);

    $html = $this->withSession(['locale' => 'en'])->get('/')->assertOk()->getContent();

    expect($html)->toContain('Ink that <span class="accent">lasts</span>')
        ->toContain('Only step')
        ->not->toContain('<details class="faq-item"');
    expect(substr_count($html, 'class="step"'))->toBe(1);
});

it('lets admins edit the home page from the dashboard', function () {
    $admin = homeAdmin();

    $this->actingAs($admin)->get(ManageHomeContent::getUrl())->assertOk();

    Livewire::actingAs($admin)
        ->test(ManageHomeContent::class)
        ->set('data.cta.title_ar', 'اطلب لوحتك اليوم')
        ->set('data.marquee.is_visible', false)
        ->call('save')
        ->assertHasNoErrors();

    $home = app(HomeContent::class)->forLocale('ar');
    expect($home['cta']['title'])->toBe('اطلب لوحتك اليوم')
        ->and($home['marquee']['visible'])->toBeFalse();
});

it('keeps the home content page away from non-admins', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['username' => 'plain-user']);

    $this->actingAs($user)->get(ManageHomeContent::getUrl())->assertForbidden();
});
