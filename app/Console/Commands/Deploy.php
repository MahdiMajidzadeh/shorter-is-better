<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $signature = 'deploy {ver}';

    protected $description = 'All you need to deploy';

    public function handle()
    {
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');

        return 0;
    }
}
