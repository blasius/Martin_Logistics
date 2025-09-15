<?php

namespace App\Providers;

use App\Models\Trip;
use App\Observers\TripObserver;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('pragmarx.google2fa', function () {
            return new Google2FA();
        });
    }

    public function boot(): void
    {
        Trip::observe(TripObserver::class);
    }
}
