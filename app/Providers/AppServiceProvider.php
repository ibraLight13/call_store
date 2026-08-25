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
use App\Domain\Identity\Policies\UserPolicy;
use App\Models\User;
use App\Support\Clock;
use App\Support\SystemClock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Piece::class, PiecePolicy::class);
        Model::preventLazyLoading(! $this->app->isProduction());

        Event::listen(PieceApproved::class, FlushDiscoveryCache::class);
        Event::listen(PieceRejected::class, FlushDiscoveryCache::class);

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
