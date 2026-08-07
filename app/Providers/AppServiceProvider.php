<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Triggers an SMS send (costs gateway credits) - keep tight.
        RateLimiter::for('otp-request', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Guards brute-forcing the 6-digit OTP.
        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
