<?php

namespace App\Providers;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
    }


    public function boot(): void
{
    RateLimiter::for('purchase_limit', function (Request $request) {
        return Limit::perMinute(24)->by($request->ip());
    });
}
}
