<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeed extends Seeder
{
    public function run()
    {
        $user = new User();
        $user->name = "Mahdi";
        $user->username = "admin";
        $user->password =  Hash::make("1234568");
        $user->is_admin = true;
        $user->is_active = true;
        $user->save();
    }
}
