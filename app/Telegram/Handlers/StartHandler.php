<?php

namespace App\Telegram\Handlers;

use App\Models\BotChat;
use SergiX44\Nutgram\Nutgram;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

/**
 * /start works before approval: a new chat gets its linking url, an
 * approved one a short welcome. Blocked chats get nothing.
 */
class StartHandler
{
    use InteractsWithBotChat;

    public function __invoke(Nutgram $bot): void
    {
        $chat = $this->botChat($bot);

        if ($chat->isBlocked()) {
            return;
        }

        if ($chat->isUsable()) {
            $project = $chat->activeProject();

            $bot->sendMessage(__('bot_replies.welcome', [
                'name'    => $chat->user->name,
                'project' => $project?->name ?? '-',
            ]));

            return;
        }

        if ($chat->status === BotChat::ACTIVE || ($chat->user && ! $chat->user->is_active)) {
            $bot->sendMessage(__('bot_replies.not_allowed'));

            return;
        }

        $bot->sendMessage(
            __('bot_replies.link_account', ['url' => url('/auth/bot/'.$chat->ensureHash())]),
            parse_mode: ParseMode::HTML,
        );
    }
}
