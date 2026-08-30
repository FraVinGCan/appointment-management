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
        RateLimiter::for('public', function (Request $request): Limit {
            return Limit::perMinute((int) config('ratelimit.public.per_minute'))
                ->by('ip:'.$request->ip());
        });

        RateLimiter::for('authenticated', function (Request $request): Limit {
            return Limit::perMinute((int) config('ratelimit.authenticated.per_minute'))
                ->by('user:'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('login', function (Request $request): array {
            $limits = [
                Limit::perMinute((int) config('ratelimit.login.ip_per_minute'))
                    ->by('ip:'.$request->ip()),
            ];
            $email = $request->string('email')->lower()->toString();

            if ($email !== '') {
                $limits[] = Limit::perMinute((int) config('ratelimit.login.email_per_minute'))
                    ->by('email:'.$email);
            }

            return $limits;
        });

        RateLimiter::for('register', function (Request $request): Limit {
            return Limit::perMinute((int) config('ratelimit.register.per_minute'))
                ->by('ip:'.$request->ip());
        });

        RateLimiter::for('booking', function (Request $request): Limit {
            return Limit::perMinute((int) config('ratelimit.booking.per_minute'))
                ->by('user:'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }
}
