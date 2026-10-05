<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

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
