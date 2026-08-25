<?php

namespace App\Providers;

use App\Domain\Catalog\Events\PieceApproved;
use App\Domain\Catalog\Events\PieceRejected;
use App\Domain\Catalog\Models\Color;
use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Theme;
use App\Domain\Catalog\Policies\PiecePolicy;
use App\Domain\Discovery\Cache\FeedCache;
use App\Domain\Discovery\Contracts\PieceSearcher;
use App\Domain\Discovery\Listeners\FlushDiscoveryCache;
use App\Domain\Discovery\Queries\MysqlFulltextSearcher;
use App\Domain\Identity\Events\UserFollowed;
use App\Domain\Identity\Notifications\NewFollower;
use App\Domain\Identity\Policies\UserPolicy;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Policies\OrderPolicy;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\FakeGateway;
use App\Domain\Payments\Gateways\StripeGateway;
use App\Domain\Social\Events\PieceLiked;
use App\Domain\Social\Events\PieceSaved;
use App\Domain\Social\Events\PieceUnliked;
use App\Domain\Social\Events\PieceUnsaved;
use App\Domain\Social\Listeners\UpdatePieceCounters;
use App\Domain\Social\Models\Board;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Policies\BoardPolicy;
use App\Domain\Social\Policies\CommentPolicy;
use App\Models\User;
use App\Support\Clock;
use App\Support\SystemClock;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(ImageManager::class, fn (): ImageManager => new ImageManager(new Driver));
        $this->app->bind(PieceSearcher::class, MysqlFulltextSearcher::class);

        $this->app->bind(PaymentGateway::class, function (): PaymentGateway {
            if (config('commerce.gateway') === 'stripe') {
                $secret = config('services.stripe.secret');

                if (is_string($secret) && $secret !== '') {
                    $webhookSecret = config('services.stripe.webhook_secret');

                    return new StripeGateway(
                        secretKey: $secret,
                        webhookSecret: is_string($webhookSecret) ? $webhookSecret : '',
                    );
                }
            }

            return new FakeGateway;
        });
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Piece::class, PiecePolicy::class);
        Gate::policy(Board::class, BoardPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Model::preventLazyLoading(! $this->app->isProduction());

        Event::listen(PieceApproved::class, FlushDiscoveryCache::class);
        Event::listen(PieceRejected::class, FlushDiscoveryCache::class);

        Event::listen(PieceLiked::class, UpdatePieceCounters::class);
        Event::listen(PieceUnliked::class, UpdatePieceCounters::class);
        Event::listen(PieceSaved::class, UpdatePieceCounters::class);
        Event::listen(PieceUnsaved::class, UpdatePieceCounters::class);

        Event::listen(UserFollowed::class, function (UserFollowed $event): void {
            $event->followed->notify(new NewFollower($event->follower));
        });

        RateLimiter::for('social-writes', fn (Request $request) => Limit::perMinute(30)->by(
            ($request->user()?->id ?: $request->ip()).'|'.$request->ip(),
        ));

        Piece::saved(fn () => FeedCache::flush());

        foreach ([Script::class, Medium::class, Theme::class, Color::class] as $taxonomy) {
            $taxonomy::saved(fn () => Cache::forget(FeedCache::TAXONOMY_KEY));
            $taxonomy::deleted(fn () => Cache::forget(FeedCache::TAXONOMY_KEY));
        }

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
