<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Channel;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('panel.projects-all', [
            'allProjects' => Project::withCount('links', 'channels', 'users')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('panel.projects-form', [
            'project'  => new Project,
            'channels' => Channel::orderBy('title')->get(),
            'users'    => User::where('is_admin', false)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = Project::create($this->validated($request));
        $this->sync($project, $request);

        $request->session()->put('project_id', $project->id);

        return redirect('projects')->with('msg-ok', __('Project created.'));
    }

    public function edit(Project $project): View
    {
        return view('panel.projects-form', [
            'project'  => $project,
            'channels' => Channel::orderBy('title')->get(),
            'users'    => User::where('is_admin', false)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validated($request, $project));
        $this->sync($project, $request);

        return redirect('projects')->with('msg-ok', __('Project saved.'));
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        if ($project->links()->exists()) {
            return redirect('projects')->with('msg-error', __('A project that still has links cannot be deleted.'));
        }

        $project->delete();
        $request->session()->forget('project_id');

        return redirect('projects')->with('msg-ok', __('Project deleted.'));
    }

    /** Switch the panel's current project. */
    public function switch(Request $request, Project $project): RedirectResponse
    {
        abort_unless($request->user()->canAccessProject($project), 403);

        $request->session()->put('project_id', $project->id);

        return redirect()->back(fallback: url('panel'));
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'name'   => 'required|string|max:100',
            'domain' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+(:\d+)?$/i',
                Rule::unique('projects', 'domain')->ignore($project?->id),
            ],
            'root_redirect_url' => 'nullable|url|max:500',
        ]);
    }

    private function sync(Project $project, Request $request): void
    {
        $channelIds = array_map('intval', $request->input('channels', []));
        $default = (int) $request->input('default_channel');

        $project->channels()->sync(collect($channelIds)->mapWithKeys(fn (int $id) => [
            $id => ['is_default' => $id === $default || (count($channelIds) === 1)],
        ])->all());

        $project->users()->sync(array_map('intval', $request->input('users', [])));
    }
}
