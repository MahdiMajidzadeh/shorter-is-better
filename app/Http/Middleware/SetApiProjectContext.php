<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * After Sanctum authenticated the key: refuse disabled owners, require the
 * key's project to still be accessible to its owner, and bind that project
 * for the controllers. API messages are always English.
 */
class SetApiProjectContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $user || ! $user->is_active) {
            throw new HttpException(401, 'This key belongs to a disabled account.');
        }

        $project = $token instanceof ApiKey ? $token->project : null;

        if (! $project || ! $user->canAccessProject($project)) {
            throw new HttpException(403, 'This key is not bound to a project you can access.');
        }

        // API messages are English regardless of the owner's panel language.
        app()->setLocale('en');
        app()->instance(Project::class, $project);

        return $next($request);
    }
}
