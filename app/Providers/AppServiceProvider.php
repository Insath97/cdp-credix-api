<?php

namespace App\Providers;

use App\Services\AllowListedSmsService;
use App\Services\CreditScoreService;
use App\Services\SmsService;
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

        // Every SMS in the system resolves SmsService out of the container --
        // NotificationService, SendSmsJob, the scheduled reminder commands and
        // the login OTP path all do, and nothing anywhere uses `new` -- so
        // this one binding puts the allow-list in front of all of them without
        // touching SmsService or any of its callers.
        //
        // See AllowListedSmsService for what to change when going live.
        $this->app->bind(SmsService::class, AllowListedSmsService::class);
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

        // A coarse ceiling on the login endpoint, not the real brute-force
        // guard -- that lives in AuthController::login(), which counts only
        // failed attempts and clears the counter the moment credentials are
        // accepted. This one counts every request, successful or not, so it
        // is deliberately generous: a branch office reaches the API from a
        // single NAT address, and twenty staff signing in at 8:30am must not
        // lock each other out. It is here to stop a flood, not a guess.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
