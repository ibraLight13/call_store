<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Catalog\PieceController;
use App\Http\Controllers\Catalog\PiecesController;
use App\Http\Controllers\Commerce\CartController;
use App\Http\Controllers\Commerce\CheckoutController;
use App\Http\Controllers\Commerce\FakePayController;
use App\Http\Controllers\Commerce\OrdersController;
use App\Http\Controllers\Commerce\RefundController;
use App\Http\Controllers\Commerce\SellerOrderController;
use App\Http\Controllers\Commerce\WebhookController;
use App\Http\Controllers\Commissions\CommissionActionController;
use App\Http\Controllers\Commissions\CommissionController;
use App\Http\Controllers\Commissions\CommissionFakePayController;
use App\Http\Controllers\Commissions\CommissionProofController;
use App\Http\Controllers\Commissions\CommissionRequestController;
use App\Http\Controllers\Commissions\ReviewController;
use App\Http\Controllers\Discovery\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Moderation\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\ProfileController as ProfileSettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Social\BoardsController;
use App\Http\Controllers\Social\CommentController;
use App\Http\Controllers\Social\NotificationsController;
use App\Http\Controllers\StatusController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])
    ->name('webhooks.stripe')
    ->withoutMiddleware([PreventRequestForgery::class]);

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(view('robots'))->header('Content-Type', 'text/plain'));
Route::get('/status', StatusController::class)->name('status');

Route::get('/explore', ExploreController::class)->name('explore');
Route::get('/pieces/{piece}', [PiecesController::class, 'show'])->name('pieces.show');

Route::get('/explore', fn () => view('pages.placeholder', ['title' => __('shell.nav_explore')]))->name('explore');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrdersController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/refund', [RefundController::class, 'store'])->name('orders.refund')->middleware('can:refund,order');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{piece}', [CartController::class, 'add'])->name('cart.add')->middleware('throttle:social-writes');
    Route::delete('/cart/{piece}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/{order}/fake-pay', [FakePayController::class, 'pay'])->name('checkout.fake_pay');

    Route::middleware('role:seller')->prefix('studio-orders')->group(function (): void {
        Route::get('/', [SellerOrderController::class, 'index'])->name('seller.orders.index');
        Route::post('/{group}/ship', [SellerOrderController::class, 'ship'])->name('seller.orders.ship');
    });

    Route::get('/settings/profile', [ProfileSettingsController::class, 'edit'])->name('settings.profile.edit');
    Route::put('/settings/profile', [ProfileSettingsController::class, 'update'])->name('settings.profile.update');

    Route::middleware('throttle:social-writes')->group(function (): void {
        Route::post('/pieces/{piece}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
        Route::post('/commissions/{commission}/reviews', [ReviewController::class, 'store'])
            ->name('reviews.store');
    });
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationsController::class, 'readAll'])->name('notifications.read_all');

    Route::get('/boards', [BoardsController::class, 'index'])->name('boards.index');
    Route::get('/boards/{board}', [BoardsController::class, 'show'])->name('boards.show');

    // Commissions — the shared buyer↔seller workspace.
    Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/request/{seller}', [CommissionRequestController::class, 'create'])
        ->name('commissions.create');
    Route::post('/commissions/request/{seller}', [CommissionRequestController::class, 'store'])
        ->name('commissions.request')->middleware('throttle:social-writes');

    Route::prefix('commissions/{commission}')->group(function (): void {
        Route::get('/', [CommissionController::class, 'show'])->name('commissions.show');
        Route::get('/proofs/{proof}', [CommissionProofController::class, 'show'])->name('commissions.proof');

        Route::middleware('throttle:social-writes')->group(function (): void {
            Route::post('/quote', [CommissionActionController::class, 'quote'])->name('commissions.quote');
            Route::post('/decline', [CommissionActionController::class, 'decline'])->name('commissions.decline');
            Route::post('/start', [CommissionActionController::class, 'start'])->name('commissions.start');
            Route::post('/proof', [CommissionActionController::class, 'submitProof'])->name('commissions.submit_proof');
            Route::post('/revision', [CommissionActionController::class, 'revision'])->name('commissions.revision');
            Route::post('/approve', [CommissionActionController::class, 'approve'])->name('commissions.approve');
            Route::post('/balance', [CommissionActionController::class, 'balance'])->name('commissions.balance');
            Route::post('/ship', [CommissionActionController::class, 'ship'])->name('commissions.ship');
            Route::post('/complete', [CommissionActionController::class, 'complete'])->name('commissions.complete');
            Route::post('/cancel', [CommissionActionController::class, 'cancel'])->name('commissions.cancel');
            Route::post('/messages', [CommissionActionController::class, 'message'])->name('commissions.message');
            Route::post('/fake-pay/{purpose}', [CommissionFakePayController::class, 'pay'])->name('commissions.fake_pay');
        });
    });
});

Route::middleware(['auth', 'role:seller'])->group(function (): void {
    Route::get('/create', [PieceController::class, 'create'])->name('create');
    Route::post('/create', [PieceController::class, 'store'])->name('create.store');
});

Route::get('/@{user:username}', [ProfileController::class, 'show'])->name('profile.show');
