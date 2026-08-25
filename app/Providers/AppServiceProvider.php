<?php

namespace App\Providers;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\Policies\PiecePolicy;
use App\Domain\Identity\Policies\UserPolicy;
use App\Models\User;
use App\Support\Clock;
use App\Support\SystemClock;
use Illuminate\Database\Eloquent\Model;
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
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Piece::class, PiecePolicy::class);
        Model::preventLazyLoading(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
