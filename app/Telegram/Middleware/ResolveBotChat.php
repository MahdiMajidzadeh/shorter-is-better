<?php

namespace App\Telegram\Middleware;

use App\Models\BotChat;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

/**
 * Runs on every update. Ignores anything that is not a private chat, keeps
 * a BotChat row for the sender, exposes it as `$bot->get('botChat')` and
 * applies the bound user's locale to the replies.
 */
class ResolveBotChat
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $chat = $bot->chat();
        $type = $chat?->type instanceof ChatType ? $chat->type->value : $chat?->type;

        if (! $chat || $type !== ChatType::PRIVATE->value) {
            return;
        }

        $from = $bot->user();

        $botChat = BotChat::firstOrNew(['chat_id' => $chat->id]);
        $botChat->fill([
            'telegram_user_id' => $from?->id ?? $chat->id,
            'name'             => trim(($from?->first_name ?? $chat->first_name ?? '').' '.($from?->last_name ?? $chat->last_name ?? '')) ?: null,
            'username'         => $from?->username ?? $chat->username,
            'last_seen_at'     => now(),
        ]);
        $botChat->save();
        $botChat->load('user', 'project');

        app()->setLocale($botChat->user?->locale ?: config('app.locale'));

        $bot->set('botChat', $botChat);

        $next($bot);
    }
}
