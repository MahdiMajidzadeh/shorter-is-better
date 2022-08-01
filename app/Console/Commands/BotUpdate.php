<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DefStudio\Telegraph\Models\TelegraphBot;

class BotUpdate extends Command
{
    protected $signature = 'bot:update';

    protected $description = 'Update Bots command';

    public function handle()
    {
        $bots = TelegraphBot::all();

        foreach ($bots as $bot) {
            $bot->unregisterCommands()->send();

            $v = $bot->registerCommands([
                'shortkey'   => 'short url with custom key',
                'forchannel' => 'make short for channel',
                'report'     => 'short report of last 7 days',
                'short'      => 'short url',
                'stat'       => 'show stat for shorted url',
                'bulk'       => 'short bulk of links in text',
                'auth'       => 'authenticate bot with your account',
            ])->send();

            $this->info($bot->name.' updated!');
        }

        $this->info('Happy Shorting!!!');
    }
}
