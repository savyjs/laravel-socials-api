<?php

namespace App\Providers;

use Dotenv\Dotenv;
use Dotenv\Loader\Loader;
use http\Env;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
       // dd(env('APP_NAME'));

    }
}
