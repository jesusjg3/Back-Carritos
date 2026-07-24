<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\User;
use App\Observers\UserObserver;
use App\Models\Trip;
use App\Observers\TripObserver;
use App\Models\Destination;
use App\Observers\DestinationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Trip::observe(TripObserver::class);
        Destination::observe(DestinationObserver::class);
    }
}



