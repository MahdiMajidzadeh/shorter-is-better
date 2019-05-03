<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function loginSubmit(Request $request)
    {
        $username = $request->get('username');
        $password = $request->get('password');
        if (Auth::attempt(['username' => $username, 'password' => $password])) {
            return redirect('panel');
        }
        else{
            return redirect('auth')->with('msg-err', '');
        }
    }

    public function logout()
    {
        Auth::logout();
    }
}
