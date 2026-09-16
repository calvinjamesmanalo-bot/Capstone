<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        ini_set('session.use_strict_mode', config('session.strict_mode', true) ? '1' : '0');

        if (config('security.https.force', false)) {
            URL::forceScheme('https');
        }

        RateLimiter::for('web', function (Request $request) {
            $key = $request->user()
                ? 'user:'.$request->user()->getAuthIdentifier()
                : 'ip:'.($request->ip() ?: 'unknown');

            return Limit::perMinute(max(1, (int) config('security.rate_limiting.web_requests_per_minute', 120)))
                ->by($key);
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(max(1, (int) config('security.authentication.endpoint_attempts_per_minute', 30)))
                ->by($request->ip() ?: 'unknown');
        });
    }
}
