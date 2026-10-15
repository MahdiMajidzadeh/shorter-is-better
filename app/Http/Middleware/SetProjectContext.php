<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

/**
 * Picks the panel's current project for the signed-in user: the one kept in
 * the session when still accessible, otherwise the first one they can see.
 * Also applies the user's locale.
 */
class SetProjectContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        app()->setLocale($user->locale ?: config('app.locale'));
        Carbon::setLocale(app()->getLocale());

        $projects = $user->accessibleProjectList();
        $current = $projects->firstWhere('id', $request->session()->get('project_id')) ?? $projects->first();

        if ($current) {
            $request->session()->put('project_id', $current->id);
            app()->instance(Project::class, $current);
        }

        View::share('projects', $projects);
        View::share('currentProject', $current);

        return $next($request);
    }
}
