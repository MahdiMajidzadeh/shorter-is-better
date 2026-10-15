<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * The root of every domain. A project domain redirects to the url set on
 * the project; the primary domain falls back to the panel.
 */
class RootController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $project = Project::findByHost($request->getHttpHost());

        if ($project?->root_redirect_url) {
            return redirect()->away($project->root_redirect_url, 302);
        }

        if ($request->getHttpHost() === Project::domainFromUrl(config('app.url'))) {
            return redirect('panel');
        }

        abort(404);
    }
}
