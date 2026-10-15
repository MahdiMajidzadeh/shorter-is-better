<?php

namespace App\Http\Webhooks\State;

use App\Models\Channel;
use App\Services\PageMeta;
use App\Services\PostComposer;
use App\Http\Webhooks\StateManager;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;

class ForChannel extends StateManager
{
    /**
     * Prefix of the inline-keyboard action names routed back to this state
     * through Handler::keyboard_handler().
     */
    protected string $actionPrefix = 'channel_';

    public function handleStep1()
    {
        if (! $this->channel()) {
            $this->chat->message('this project has no channel yet')->send();
            $this->done();

            return;
        }

        $this->chat->message('send your url')->send();
        $this->nextStep();
    }

    public function handleStep2()
    {
        if (! $this->receiveUrl()) {
            return;
        }

        $this->sendPreview();
        $this->nextStep();
    }

    public function handleStep3()
    {
        $this->handleKeyboard();
    }

    public function handleStep4()
    {
        $this->publish($this->message);
        $this->done();
    }

    /**
     * Validate the article url sent by the user, shorten it and fetch its
     * Open Graph data. Returns false (after replying) when the url is rejected.
     */
    protected function receiveUrl(): bool
    {
        $this->chat->storage()->set('data.url', $this->message);

        if (! $this->isUrl($this->message)) {
            $this->chat->message('url is not valid')->send();

            return false;
        } elseif ($this->doesUrlRedirect($this->message)) {
            $this->chat->message('url is redirected, send original url')->send();

            return false;
        }

        $this->chat->storage()->set('data.url_short', $this->makeShort($this->message));
        $this->chat->storage()->set('data.link_data', app(PageMeta::class)->fetch($this->message));

        return true;
    }

    protected function sendPreview(): void
    {
        $post = $this->composePost();
        $this->chat->storage()->set('data.messageText', $post['text']);

        $response = $this->chat->markdown($post['text'])
            ->keyboard(Keyboard::make()->buttons([
                Button::make('Confirm')->action('keyboard_handler')->param('action_name', $this->actionPrefix.'confirm'),
                Button::make('Edit')->action('keyboard_handler')->param('action_name', $this->actionPrefix.'edit'),
                Button::make('Dismiss')->action('keyboard_handler')->param('action_name', $this->actionPrefix.'dismiss'),
            ]))->send();

        $this->chat->storage()->set('data.last_id', $response->telegraphMessageId());
    }

    protected function handleKeyboard(): void
    {
        // Read before dispatching: confirm/dismiss call done(), which clears data.
        $lastId = $this->chat->storage()->get('data.last_id');
        $action = str_replace($this->actionPrefix, '', $this->message);

        $this->$action();

        $this->chat->deleteKeyboard($lastId)->send();
    }

    protected function confirm(): void
    {
        $this->publish($this->chat->storage()->get('data.messageText'));
        $this->done();
    }

    protected function edit(): void
    {
        $this->chat->markdown('sent your replacement text:')->send();
        $this->nextStep();
    }

    protected function dismiss(): void
    {
        $this->chat->markdown('ok')->send();
        $this->done();
    }

    protected function publish(string $text): void
    {
        $channel = $this->channel();
        $post = $this->composePost();
        $data = $this->chat->storage()->get('data.link_data');

        $msg = $this->channelChat($channel)->markdown($text);

        if (isset($data['image']) && strlen($data['image']) > 1) {
            $msg = $msg->photo($data['image']);
        }

        $msg->keyboard($this->channelKeyboard($post['buttons']))
            ->withoutPreview()
            ->send();
    }

    /** The channel this post goes to: the project's default channel. */
    protected function channel(): ?Channel
    {
        return $this->project()?->defaultChannel();
    }

    /**
     * Telegraph can only send to chats it knows, so make sure the channel
     * has a row bound to the bot that serves this chat.
     */
    protected function channelChat(Channel $channel): TelegraphChat
    {
        return TelegraphChat::firstOrCreate(
            ['chat_id' => $channel->chat_id, 'telegraph_bot_id' => $this->chat->telegraph_bot_id],
            ['name' => $channel->title],
        );
    }

    /**
     * @return array{text: string, buttons: list<array{label: string, url: string}>}
     */
    protected function composePost(): array
    {
        $data = $this->chat->storage()->get('data.link_data') ?? [];

        return app(PostComposer::class)->compose($this->channel(), [
            'title'        => $data['title'] ?? '',
            'description'  => $data['description'] ?? '',
            'short_url'    => $this->chat->storage()->get('data.url_short'),
            'original_url' => $this->chat->storage()->get('data.url'),
            'feed_url'     => $this->chat->storage()->get('data.feed_short'),
        ]);
    }

    /**
     * Inline keyboard attached to the channel post. Every button goes on its
     * own row.
     */
    protected function channelKeyboard(array $buttons): Keyboard
    {
        return Keyboard::make()->buttons(array_map(
            fn (array $button) => Button::make($button['label'])->url($button['url']),
            $buttons,
        ));
    }

    protected function doesUrlRedirect($url): bool
    {
        $headers = @get_headers($url, true);

        return ! empty($headers['Location']);
    }
}
