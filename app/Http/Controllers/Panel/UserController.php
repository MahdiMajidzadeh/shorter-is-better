<?php

namespace App\Http\Controllers\Panel;

use App\Model\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function root()
    {
        $data['users'] = User::all();

        return view('users_all', $data);
    }
}
