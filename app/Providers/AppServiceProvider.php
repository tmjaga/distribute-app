<?php

namespace App\Providers;

use App\Contracts\DriverDistributor;
use App\Contracts\MapLocation;
use App\Services\GreedyDistributorService;
use App\Services\DriverLocationService;
use App\Services\HungarianDistributorService;
use App\Services\DriverOsrmLocationService;
use App\Services\DriverRoadsLocationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Driver location services
        // $this->app->singleton(MapLocation::class, DriverLocationService::class);
        // $this->app->singleton(MapLocation::class, DriverOsrmLocationService::class);
        $this->app->singleton(MapLocation::class, DriverRoadsLocationService::class);

        // Distributor services
        $this->app->singleton(DriverDistributor::class, GreedyDistributorService::class);
        // $this->app->singleton(DriverDistributor::class, HungarianDistributorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
