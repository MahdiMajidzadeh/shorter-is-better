<?php

namespace App\Telegram\Conversations;

use App\Models\Link;
use App\Services\Shortener;
use SergiX44\Nutgram\Nutgram;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

class Bulk extends Conversation
{
    use InteractsWithBotChat;

    public function start(Nutgram $bot): void
    {
        $bot->sendMessage(__('bot_replies.send_bulk'));
        $this->next('receive');
    }

    public function receive(Nutgram $bot): void
    {
        $text = (string) ($bot->message()?->text ?? '');

        $result = app(Shortener::class)->bulk($this->botProject($bot), $this->botUser($bot), $text, Link::SOURCE_BOT);

        if ($result['links']->isEmpty()) {
            $bot->sendMessage(__('bot_replies.bulk_none'));

            return;
        }

        foreach (mb_str_split($result['text'], 4000) as $chunk) {
            $bot->sendMessage($chunk, link_preview_options: LinkPreviewOptions::make(is_disabled: true));
        }

        $bot->sendMessage(__('bot_replies.bulk_done', ['count' => local_number($result['links']->count())]));
        $this->end();
    }
}
