<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * API keys for the current project. Users manage their own keys; admins
 * see everyone's. The plain token is flashed once after creation.
 */
class ApiKeyController extends Controller
{
    public function index(Request $request): View
    {
        $project = $this->project();

        $keys = ApiKey::query()
            ->where('project_id', $project->id)
            ->when(! $request->user()->is_admin, fn ($q) => $q->where('tokenable_id', $request->user()->id))
            ->with('tokenable')
            ->orderByDesc('id')
            ->get();

        return view('panel.api-keys', ['keys' => $keys, 'scopes' => ApiKey::SCOPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'scopes'   => 'required|array|min:1',
            'scopes.*' => Rule::in(array_keys(ApiKey::SCOPES)),
        ]);

        $token = ApiKey::issue($request->user(), $this->project(), $data['name'], $data['scopes']);

        return redirect('api-keys')
            ->with('msg-ok', __('API key created. Copy it now; it is not shown again.'))
            ->with('plain_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $key = ApiKey::query()
            ->where('project_id', $this->project()->id)
            ->when(! $request->user()->is_admin, fn ($q) => $q->where('tokenable_id', $request->user()->id))
            ->findOrFail($id);

        $key->delete();

        return redirect('api-keys')->with('msg-ok', __('API key revoked.'));
    }
}
