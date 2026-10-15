<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * The panel (and later the API and mini app) is served only on the primary
 * APP_URL domain. A request that arrives on another project's short domain
 * gets a 404 so sessions and CSRF stay single-host. Hosts that match no
 * project are let through, which keeps `artisan serve` on any port working.
 */
class EnsurePrimaryHost
{
    public function handle(Request $request, Closure $next)
    {
        $host = strtolower($request->getHttpHost());

        if ($host !== Project::domainFromUrl(config('app.url')) && Project::findByHost($host)) {
            abort(404);
        }

        return $next($request);
    }
}
