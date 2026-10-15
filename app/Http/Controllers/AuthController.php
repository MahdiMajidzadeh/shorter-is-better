<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BotChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if (auth()->check()) {
            return redirect('panel');
        }

        return view('auth.login');
    }

    public function loginSubmit(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required|min:6',
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if ($user && $user->is_active && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $remember = true);

            $request->session()->regenerate();

            return redirect('panel');
        }

        return back()
            ->withErrors(['username' => __('Invalid username or password.')])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function hash(Request $request, $hash)
    {
        $chat = BotChat::where('hash', $hash)->first();

        if (! $chat || $chat->isBlocked()) {
            abort(404);
        }

        $user = $request->user();

        $chat->forceFill([
            'user_id'    => $user->id,
            'project_id' => null,
            'status'     => $user->is_admin ? BotChat::ACTIVE : BotChat::PENDING,
        ])->save();

        return redirect('panel')->with('msg-ok', $user->is_admin
            ? __('Your Telegram chat is now linked to this account.')
            : __('Your Telegram chat is linked and waits for an admin to approve it.'));
    }
}
