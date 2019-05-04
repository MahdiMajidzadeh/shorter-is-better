<?php

namespace App\Console\Commands;

use App\Model\User;
use Illuminate\Console\Command;

class Install extends Command
{
    protected $signature = 'install';

    protected $description = 'Install project';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->call('migrate:fresh');
        $this->info('Migration is done!');

        $name = $this->ask('What is your name?');
        $username = $this->ask('What is your username?');
        $password = $this->secret('What is the password?');

        $user = New User();
        $user->name = $name;
        $user->username = $username;
        $user->password = \Hash::make($password);
        $user->save();

        $this->info('Admin user created!');
    }
}
