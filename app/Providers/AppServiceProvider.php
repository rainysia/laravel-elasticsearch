<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\EsConfigService', function () {
            return new \App\Services\EsConfigService();
        });
        $this->app->bind('App\Services\EsDataService', function () {
            return new \App\Services\EsDataService();
        });
    }
}
