<?php

namespace App\Telegram\Handlers;

use App\Models\Link;
use App\Services\Shortener;
use SergiX44\Nutgram\Nutgram;
use App\Exceptions\ShortenerException;
use App\Telegram\Support\InteractsWithBotChat;

/**
 * A bare message outside any conversation: a url gets shortened on the
 * spot, anything else gets the help text.
 */
class FallbackHandler
{
    use InteractsWithBotChat;

    public function __invoke(Nutgram $bot, Shortener $shortener): void
    {
        $text = trim($bot->message()?->text ?? '');

        if ($text === '' || ! $shortener->isValidUrl($text)) {
            $bot->sendMessage(__('bot_replies.unknown'));

            return;
        }

        try {
            $link = $shortener->make($this->botProject($bot), $this->botUser($bot), $text, Link::SOURCE_BOT);
        } catch (ShortenerException $e) {
            $bot->sendMessage($e->getMessage());

            return;
        }

        $bot->sendMessage($link->default_short_url);
    }
}
