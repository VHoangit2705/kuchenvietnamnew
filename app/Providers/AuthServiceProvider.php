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
        Passport::tokensCan(['string' => 'string',]);
        Passport::tokensExpireIn(Carbon::now()->addHours(2));

        // Định nghĩa Gate toàn cục sử dụng hasPermission của User model
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasPermission')) {
                if ($user->hasPermission($ability)) {
                    return true;
                }
            }
            return null; // Tiếp tục kiểm tra các gate khác nếu có
        });
    }
}
