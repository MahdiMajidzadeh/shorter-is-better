<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeed extends Seeder
{
    public function run()
    {
        $this->seedProject();

        if (User::where('username', 'admin')->exists()) {
            $this->command?->info('Admin user already exists, skipping.');

            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::random(16);

        $user = new User;
        $user->name = 'Admin';
        $user->username = 'admin';
        $user->password = Hash::make($password);
        $user->is_admin = true;
        $user->is_active = true;
        $user->save();

        if (env('ADMIN_PASSWORD')) {
            $this->command?->info('Admin user created with the password from ADMIN_PASSWORD.');
        } else {
            $this->command?->warn("Admin user created with generated password: {$password}");
            $this->command?->warn('Store it now — it is not shown again. Set ADMIN_PASSWORD in .env to choose your own.');
        }
    }

    /** The first project is the primary domain from APP_URL. */
    private function seedProject(): void
    {
        if (Project::exists()) {
            return;
        }

        Project::create([
            'name'   => config('app.name', 'Shorter'),
            'domain' => Project::domainFromUrl(config('app.url')),
        ]);

        $this->command?->info('Default project created for '.config('app.url').'.');
    }
}
