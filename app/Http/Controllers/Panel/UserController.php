<?php

namespace App\Http\Controllers\Panel;

use App\Model\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function root()
    {
        $data['users'] = User::all();

        return view('users_all', $data);
    }

    public function create()
    {
        return view('users_create');
    }

    public function createSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users',
            'password' => 'required'
        ]);

        $user = New User();
        $user->name = $request->get('name');
        $user->username = $request->get('username');
        $user->password = Hash::make($request->get('password'));
        $user->save();

        return redirect('panel/users')->with('msg-ok','');
    }
}
