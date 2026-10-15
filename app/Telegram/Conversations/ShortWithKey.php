<?php

namespace App\Telegram\Conversations;

use App\Models\Link;
use App\Services\Shortener;
use SergiX44\Nutgram\Nutgram;
use App\Exceptions\ShortenerException;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Conversations\Conversation;

class ShortWithKey extends Conversation
{
    use InteractsWithBotChat;

    protected string $url = '';

    public function start(Nutgram $bot): void
    {
        $bot->sendMessage(__('bot_replies.send_url'));
        $this->next('receiveUrl');
    }

    public function receiveUrl(Nutgram $bot): void
    {
        $url = trim($bot->message()?->text ?? '');

        if (! app(Shortener::class)->isValidUrl($url)) {
            $bot->sendMessage(__('bot_replies.invalid_url'));

            return;
        }

        $this->url = $url;
        $bot->sendMessage(__('bot_replies.send_key'));
        $this->next('receiveKey');
    }

    public function receiveKey(Nutgram $bot): void
    {
        $key = trim($bot->message()?->text ?? '');

        try {
            $link = app(Shortener::class)->make($this->botProject($bot), $this->botUser($bot), $this->url, Link::SOURCE_BOT, $key);
        } catch (ShortenerException $e) {
            $bot->sendMessage($e->getMessage());

            return;
        }

        $bot->sendMessage($link->default_short_url);
        $this->end();
    }
}
