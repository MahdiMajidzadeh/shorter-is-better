<?php

namespace App\Http\Controllers;

use App\Models\User;
use DefStudio\Telegraph\Models\TelegraphChat;
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

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $remember = true);

            return redirect('panel');
        }

        return back();
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
        $chat = TelegraphChat::where('hash', $hash)->first();

        if (! $chat) {
            return abort();
        }

        $chat->user_id = auth()->id();
        $chat->save();

        return redirect('panel')->with('msg-ok', 'Your Bot is Active now!');
    }
}
