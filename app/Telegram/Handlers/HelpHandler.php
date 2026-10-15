<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use App\Telegram\Support\InteractsWithBotChat;

class HelpHandler
{
    use InteractsWithBotChat;

    public function __invoke(Nutgram $bot): void
    {
        $project = $this->botProject($bot);

        $bot->sendMessage(__('bot_replies.help', ['project' => $project->name, 'domain' => $project->domain]));
    }
}
