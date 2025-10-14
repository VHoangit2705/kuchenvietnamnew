<?php

namespace App\Providers;

// use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as BaseAuthServiceProvider;

class AuthServiceProvider extends BaseAuthServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        Passport::enablePasswordGrant();
        Passport::tokensCan(['string'  => 'string',]);
        Passport::tokensExpireIn(Carbon::now()->addHours(2));
    }
}
