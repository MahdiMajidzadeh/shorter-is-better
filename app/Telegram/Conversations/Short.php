<?php

namespace App\Telegram\Conversations;

use App\Models\Link;
use App\Services\Shortener;
use SergiX44\Nutgram\Nutgram;
use App\Exceptions\ShortenerException;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Conversations\Conversation;

class Short extends Conversation
{
    use InteractsWithBotChat;

    public function start(Nutgram $bot): void
    {
        $bot->sendMessage(__('bot_replies.send_url'));
        $this->next('receive');
    }

    public function receive(Nutgram $bot): void
    {
        $url = trim($bot->message()?->text ?? '');

        try {
            $link = app(Shortener::class)->make($this->botProject($bot), $this->botUser($bot), $url, Link::SOURCE_BOT);
        } catch (ShortenerException $e) {
            $bot->sendMessage($e->getMessage());

            return;
        }

        $bot->sendMessage($link->default_short_url);
        $this->end();
    }
}
