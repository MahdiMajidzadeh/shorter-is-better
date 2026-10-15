<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

/** Who am I: the key's name, scopes and project. Handy for checking a key. */
class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        $project = $this->project();

        return response()->json([
            'key'     => ['name' => $token->name, 'scopes' => $token->scopes()],
            'user'    => ['id' => $request->user()->id, 'name' => $request->user()->name],
            'project' => ['id' => $project->id, 'name' => $project->name, 'domain' => $project->domain, 'base_url' => $project->baseUrl()],
        ]);
    }
}
