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

            $v = $bot->registerCommands(bot_commands())->send();

            $this->info($bot->name.' updated!');
        }

        $this->info('Happy Shorting!!!');
    }
}
