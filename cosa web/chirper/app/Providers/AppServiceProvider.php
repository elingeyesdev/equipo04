<?php

namespace App\Providers;

use App\Contracts\ElevationProvider;
use App\Models\Reporte;
use App\Services\ElevationService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        View::composer('layouts.app', function ($view) {
            $count = 0;
            if (session()->has('api_token')) {
                $user = (array) session('api_user', []);
                if (($user['role'] ?? '') === 'authority') {
                    $count = Reporte::query()
                        ->whereNull('inundacion_id')
                        ->where('estado_validacion', Reporte::VALIDACION_PENDIENTE)
                        ->count();
                }
            }
            $view->with('reportsPendientesCount', $count);
        });
    }
}
