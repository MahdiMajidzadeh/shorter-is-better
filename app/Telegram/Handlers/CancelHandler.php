<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;

class CancelHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->endConversation();
        $bot->sendMessage(__('bot_replies.cancelled'));
    }
}
