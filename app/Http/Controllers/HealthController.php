<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class HealthController extends Controller
{
    /**
     * Liveness probe for the container orchestrator.
     *
     * Intentionally free of database or cache access: this answers "did the
     * application boot?", not "are its dependencies healthy?". Mixing the two
     * turns a transient database outage into a container restart loop.
     */
    public function __invoke(): Response
    {
        return response('ok', 200)
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store');
    }
}
