<?php

namespace App\Providers;

use App\Contracts\ElevationProvider;
use App\Services\ElevationService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ElevationProvider::class, ElevationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local')) {
            URL::forceScheme('http');
            URL::forceRootUrl(config('app.url'));
        }
    }
}
