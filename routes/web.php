<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('pages.home'))->name('home');

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
    Route::get('/profile', fn () => view('pages.placeholder', ['title' => __('shell.account'), 'withLogout' => true]))->name('profile');
});

Route::middleware(['auth', 'role:seller'])->group(function (): void {
    Route::get('/create', fn () => view('pages.placeholder', ['title' => __('shell.nav_create')]))->name('create');
});
