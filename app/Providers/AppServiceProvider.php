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
        // Referenced by routes/v1.php as throttle:login. It was defined here
        // when that middleware was added, then dropped in b2bbe57 while the
        // route kept pointing at it -- which made every POST /login throw
        // MissingRateLimiterException and answer 500 before the controller ran.
        //
        // Deliberately loose: AuthController does the real work with a
        // per-account key that counts only FAILED attempts, so this is just an
        // outer ceiling on request volume from one address. Tightening it would
        // lock out a whole office behind a single NAT address on successful
        // logins alone.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Triggers an SMS send (costs gateway credits) - keep tight.
        RateLimiter::for('otp-request', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Guards brute-forcing the 6-digit OTP.
        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Login form/auth endpoint throttle so attackers cannot spray the
        // password without tripping a block. Keyed on email (when present) so a
        // distributed attack on one account hits a shared, per-minute cap.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('username', $request->ip()));
        });
    }
}
