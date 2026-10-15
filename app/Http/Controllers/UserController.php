<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BotChat;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function index(): View
    {
        return view('panel.users-all', [
            'users' => User::with('projects')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('panel.users-form', [
            'user'        => new User(['is_active' => true, 'locale' => 'fa']),
            'allProjects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $user->projects()->sync(array_map('intval', $request->input('projects', [])));

        return redirect('users')->with('msg-ok', __('User created.'));
    }

    public function edit(User $user): View
    {
        return view('panel.users-form', [
            'user'        => $user,
            'allProjects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        if ($user->id === $request->user()->id) {
            $data['is_admin'] = true;
            $data['is_active'] = true;
        }

        $user->update($data);
        $user->projects()->sync(array_map('intval', $request->input('projects', [])));

        return redirect('users')->with('msg-ok', __('User saved.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect('users')->with('msg-error', __('You cannot delete your own account.'));
        }

        BotChat::where('user_id', $user->id)->update(['user_id' => null, 'status' => BotChat::PENDING]);
        $user->delete();

        return redirect('users')->with('msg-ok', __('User deleted.'));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name'      => 'required|string|max:100',
            'username'  => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user?->id)],
            'password'  => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'is_admin'  => 'boolean',
            'is_active' => 'boolean',
            'locale'    => ['required', Rule::in(['fa', 'en'])],
        ]);
    }
}
