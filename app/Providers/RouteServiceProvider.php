<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // Container health probe. Registered without any middleware group
            // so it never touches sessions, the cache or a rate limiter — it
            // must stay answerable while those dependencies are degraded.
            Route::get('up', HealthController::class);

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // One bucket per API key, falling back to the caller's IP before auth.
        RateLimiter::for('api', function (Request $request) {
            // Throttling runs before route auth, so resolve the key through the sanctum guard.
            $token = $request->user('sanctum')?->currentAccessToken();

            return Limit::perMinute(120)->by($token?->id ? 'key:'.$token->id : 'ip:'.$request->ip());
        });
    }
}
