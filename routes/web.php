<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Catalog\PieceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\ProfileController as ProfileSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/explore', fn () => view('pages.placeholder', ['title' => __('shell.nav_explore')]))->name('explore');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/cart', fn () => view('pages.placeholder', ['title' => __('shell.nav_cart')]))->name('cart');
    Route::get('/orders', fn () => view('pages.placeholder', ['title' => __('shell.nav_orders')]))->name('orders');

    Route::get('/settings/profile', [ProfileSettingsController::class, 'edit'])->name('settings.profile.edit');
    Route::put('/settings/profile', [ProfileSettingsController::class, 'update'])->name('settings.profile.update');
});

Route::middleware(['auth', 'role:seller'])->group(function (): void {
    Route::get('/create', [PieceController::class, 'create'])->name('create');
    Route::post('/create', [PieceController::class, 'store'])->name('create.store');
});

Route::get('/@{user:username}', [ProfileController::class, 'show'])->name('profile.show');
