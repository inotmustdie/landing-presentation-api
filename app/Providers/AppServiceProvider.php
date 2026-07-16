<?php

namespace App\Providers;

use App\Repositories\Contracts\MetricsRepositoryInterface;
use App\Repositories\FileMetricsRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MetricsRepositoryInterface::class, FileMetricsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
