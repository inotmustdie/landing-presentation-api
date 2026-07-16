<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('contact-form', function (Request $request) {
            $maxAttempts = (int) config('services.contact.rate_limit_max_attempts', 5);
            $decaySeconds = (int) config('services.contact.rate_limit_decay_seconds', 600);
            $email = (string) $request->input('email', 'guest');

            return Limit::perMinutes(max(1, (int) ceil($decaySeconds / 60)), $maxAttempts)
                ->by($request->ip().'|'.$email)
                ->response(function (Request $request, array $headers) use ($maxAttempts, $decaySeconds) {
                    return response()->json([
                        'message' => 'Too many contact requests. Please try again later.',
                        'meta' => [
                            'max_attempts' => $maxAttempts,
                            'decay_seconds' => $decaySeconds,
                        ],
                    ], 429, $headers);
                });
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
