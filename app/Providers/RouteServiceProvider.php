<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Đăng ký các route của ứng dụng.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    /**
     * Đăng ký các route API của ứng dụng.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace('App\Http\Controllers\Api')
            ->group(base_path('routes/api.php')); // Loại bỏ namespace($this->namespace)
    }

    /**
     * Đăng ký các route web của ứng dụng.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php')); // Loại bỏ namespace($this->namespace)
    }

    /**
     * Đăng ký các dịch vụ trong container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
