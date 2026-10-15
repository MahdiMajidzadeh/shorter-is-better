<?php

namespace App\Telegram\Middleware;

use App\Models\BotChat;
use SergiX44\Nutgram\Nutgram;

/**
 * The access gate. Blocked chats get silence, pending ones a reminder to
 * finish linking, and chats without a usable project are told so. The
 * /start handler skips this so a new user can obtain their link.
 */
class EnsureBotAccess
{
    public function __invoke(Nutgram $bot, $next): void
    {
        /** @var BotChat|null $chat */
        $chat = $bot->get('botChat');

        if (! $chat || $chat->isBlocked()) {
            return;
        }

        if (! $chat->isUsable()) {
            $bot->sendMessage(__('bot_replies.not_allowed'));

            return;
        }

        if (! $chat->activeProject()) {
            $bot->sendMessage(__('bot_replies.no_project'));

            return;
        }

        $next($bot);
    }
}
