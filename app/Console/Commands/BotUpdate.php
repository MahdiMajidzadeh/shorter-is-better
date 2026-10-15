<?php

namespace App\Console\Commands;

use App\Support\BotRegistrar;
use Illuminate\Console\Command;

class BotUpdate extends Command
{
    protected $signature = 'bot:update';

    protected $description = 'Register the Telegram webhook and command list';

    public function handle(BotRegistrar $registrar): int
    {
        if (! config('nutgram.token')) {
            $this->error('TELEGRAM_TOKEN is not set.');

            return self::FAILURE;
        }

        $registrar->register();

        $this->info('Webhook set to '.BotRegistrar::webhookUrl().' and commands registered.');

        return self::SUCCESS;
    }
}
