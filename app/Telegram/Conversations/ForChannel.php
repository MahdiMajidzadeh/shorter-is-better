<?php

namespace App\Telegram\Conversations;

use Throwable;
use App\Models\Link;
use App\Models\Channel;
use App\Services\PageMeta;
use App\Services\Shortener;
use SergiX44\Nutgram\Nutgram;
use App\Services\PostComposer;
use App\Services\ChannelPublisher;
use Illuminate\Support\Facades\Http;
use App\Exceptions\ShortenerException;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * /for_channel: pick a channel (when the project has several), send a url,
 * get a preview rendered from the channel's template, then confirm, edit
 * or dismiss. The short link is created on the chat's active project.
 */
class ForChannel extends Conversation
{
    use InteractsWithBotChat;

    protected bool $withFeed = false;

    protected ?int $channelId = null;

    protected string $url = '';

    protected string $shortUrl = '';

    protected array $meta = [];

    protected string $feedUrl = '';

    protected string $feedShort = '';

    protected string $messageText = '';

    protected array $buttons = [];

    protected ?int $previewMessageId = null;

    public function start(Nutgram $bot): void
    {
        $channels = $this->botProject($bot)->channels()->orderByDesc('channel_project.is_default')->orderBy('channels.id')->get();

        if ($channels->isEmpty()) {
            $bot->sendMessage(__('bot_replies.no_channel'));
            $this->end();

            return;
        }

        if ($channels->count() === 1) {
            $this->channelId = $channels->first()->id;
            $this->askUrl($bot);

            return;
        }

        $keyboard = InlineKeyboardMarkup::make();

        foreach ($channels as $channel) {
            $keyboard->addRow(InlineKeyboardButton::make($channel->title, callback_data: 'channel:'.$channel->id));
        }

        $bot->sendMessage(__('bot_replies.choose_channel'), reply_markup: $keyboard);
        $this->next('chooseChannel');
    }

    public function chooseChannel(Nutgram $bot): void
    {
        $id = $this->callbackValue($bot, 'channel');

        if ($id === null || ! $this->botProject($bot)->channels()->where('channels.id', $id)->exists()) {
            $bot->sendMessage(__('bot_replies.use_buttons'));

            return;
        }

        $this->channelId = (int) $id;
        $this->clearKeyboard($bot);
        $this->askUrl($bot);
    }

    protected function askUrl(Nutgram $bot): void
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

        if ($this->urlRedirects($url)) {
            $bot->sendMessage(__('bot_replies.url_redirects'));

            return;
        }

        try {
            $link = app(Shortener::class)->make($this->botProject($bot), $this->botUser($bot), $url, Link::SOURCE_BOT, channel: $this->channel());
        } catch (ShortenerException $e) {
            $bot->sendMessage($e->getMessage());

            return;
        }

        $this->url = $url;
        $this->shortUrl = $link->default_short_url;
        $this->meta = app(PageMeta::class)->fetch($url);

        if ($this->withFeed) {
            $bot->sendMessage(__('bot_replies.send_feed_url'));
            $this->next('receiveFeed');

            return;
        }

        $this->preview($bot);
    }

    public function receiveFeed(Nutgram $bot): void
    {
        $url = trim($bot->message()?->text ?? '');

        try {
            $link = app(Shortener::class)->make($this->botProject($bot), $this->botUser($bot), $url, Link::SOURCE_BOT, channel: $this->channel());
        } catch (ShortenerException) {
            $bot->sendMessage(__('bot_replies.invalid_feed_url'));

            return;
        }

        $this->feedUrl = $url;
        $this->feedShort = $link->default_short_url;
        $this->preview($bot);
    }

    protected function preview(Nutgram $bot): void
    {
        $post = app(PostComposer::class)->compose($this->channel(), [
            'title'        => $this->meta['title'] ?? '',
            'description'  => $this->meta['description'] ?? '',
            'short_url'    => $this->shortUrl,
            'original_url' => $this->url,
            'feed_url'     => $this->feedShort,
        ]);

        $this->messageText = $post['text'];
        $this->buttons = $post['buttons'];

        $message = $bot->sendMessage(
            $post['text'],
            parse_mode: ParseMode::MARKDOWN_LEGACY,
            link_preview_options: LinkPreviewOptions::make(is_disabled: true),
            reply_markup: InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make(__('bot_replies.confirm'), callback_data: 'post:confirm'),
                InlineKeyboardButton::make(__('bot_replies.edit'), callback_data: 'post:edit'),
                InlineKeyboardButton::make(__('bot_replies.dismiss'), callback_data: 'post:dismiss'),
            ),
        );

        $this->previewMessageId = $message?->message_id;
        $this->next('decide');
    }

    public function decide(Nutgram $bot): void
    {
        $action = $this->callbackValue($bot, 'post');

        if ($action === null) {
            $bot->sendMessage(__('bot_replies.use_buttons'));

            return;
        }

        $this->clearKeyboard($bot);

        match ($action) {
            'confirm' => $this->publish($bot, $this->messageText),
            'edit'    => $this->askEdit($bot),
            default   => $this->dismiss($bot),
        };
    }

    protected function askEdit(Nutgram $bot): void
    {
        $bot->sendMessage(__('bot_replies.send_replacement'));
        $this->next('receiveEdit');
    }

    public function receiveEdit(Nutgram $bot): void
    {
        $text = trim($bot->message()?->text ?? '');

        if ($text === '') {
            $bot->sendMessage(__('bot_replies.send_replacement'));

            return;
        }

        $this->publish($bot, $text);
    }

    protected function publish(Nutgram $bot, string $text): void
    {
        // With an onApiError handler registered, a refused call returns null
        // instead of throwing; the handler has already told the user why.
        $sent = app(ChannelPublisher::class)->publish($bot, $this->channel(), $text, $this->buttons, $this->meta['image'] ?? null);

        if ($sent === null) {
            $bot->sendMessage(__('bot_replies.publish_failed', ['channel' => $this->channel()->title]));
            $this->end();

            return;
        }

        $bot->sendMessage(__('bot_replies.published', ['channel' => $this->channel()->title]));
        $this->end();
    }

    protected function dismiss(Nutgram $bot): void
    {
        $bot->sendMessage(__('bot_replies.dismissed'));
        $this->end();
    }

    protected function channel(): Channel
    {
        return Channel::findOrFail($this->channelId);
    }

    /** The value after "prefix:" in a callback query, or null for any other update. */
    protected function callbackValue(Nutgram $bot, string $prefix): ?string
    {
        if (! $bot->isCallbackQuery()) {
            return null;
        }

        $data = (string) $bot->callbackQuery()?->data;
        $bot->answerCallbackQuery();

        return str_starts_with($data, $prefix.':') ? substr($data, strlen($prefix) + 1) : null;
    }

    protected function clearKeyboard(Nutgram $bot): void
    {
        $messageId = $bot->callbackQuery()?->message?->message_id;

        if ($messageId) {
            $bot->editMessageReplyMarkup(chat_id: $bot->chatId(), message_id: $messageId);
        }
    }

    /** Whether the url answers with a redirect; an unreachable url is not one. */
    protected function urlRedirects(string $url): bool
    {
        try {
            $response = Http::timeout((int) config('shortener.fetch_timeout'))
                ->withUserAgent(config('shortener.user_agent'))
                ->withoutRedirecting()
                ->get($url);
        } catch (Throwable) {
            return false;
        }

        return $response->redirect() && $response->header('Location') !== '';
    }
}
