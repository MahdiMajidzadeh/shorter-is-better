<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * The panel's current project, or a redirect to set one up when the
     * user has none.
     */
    protected function project(): Project
    {
        if (app()->bound(Project::class)) {
            return app(Project::class);
        }

        if (request()->user()?->is_admin) {
            abort(redirect('projects/create')->with('msg-error', __('Create a project before shortening links.')));
        }

        abort(response()->view('panel.no-project'));
    }
}
