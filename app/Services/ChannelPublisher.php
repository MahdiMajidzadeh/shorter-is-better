<?php

namespace App\Services;

use App\Models\Channel;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Message\Message;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * Sends a composed post to a channel: a photo with caption when the page
 * had an image, a plain message otherwise. Every button goes on its own row.
 *
 * @phpstan-type Button array{label: string, url: string}
 */
class ChannelPublisher
{
    /**
     * @param  list<Button>  $buttons
     */
    public function publish(Nutgram $bot, Channel $channel, string $text, array $buttons, ?string $image = null): ?Message
    {
        $keyboard = $this->keyboard($buttons);

        if ($image) {
            return $bot->sendPhoto(
                photo: $image,
                chat_id: $channel->chat_id,
                caption: $text,
                parse_mode: ParseMode::MARKDOWN_LEGACY,
                reply_markup: $keyboard,
            );
        }

        return $bot->sendMessage(
            text: $text,
            chat_id: $channel->chat_id,
            parse_mode: ParseMode::MARKDOWN_LEGACY,
            link_preview_options: LinkPreviewOptions::make(is_disabled: true),
            reply_markup: $keyboard,
        );
    }

    /**
     * @param  list<Button>  $buttons
     */
    public function keyboard(array $buttons): ?InlineKeyboardMarkup
    {
        if ($buttons === []) {
            return null;
        }

        $keyboard = InlineKeyboardMarkup::make();

        foreach ($buttons as $button) {
            $keyboard->addRow(InlineKeyboardButton::make($button['label'], url: $button['url']));
        }

        return $keyboard;
    }
}
