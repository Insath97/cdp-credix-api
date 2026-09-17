<?php

namespace App\Providers;

use App\Services\CreditScoreService;
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
        // Singleton so its memoised settings snapshot survives a whole request.
        // Customer::getCreditScoreBandAttribute() resolves this once per
        // serialised customer, and CACHE_STORE is `database` -- a fresh
        // instance per call would turn a 15-row customer list into 75 cache
        // queries for five values that cannot change mid-request.
        $this->app->singleton(CreditScoreService::class);
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
