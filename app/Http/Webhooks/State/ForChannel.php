<?php

namespace App\Http\Webhooks\State;

use Goutte\Client;
use shweshi\OpenGraph\OpenGraph;
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
        $this->getOpenGraph();

        return true;
    }

    protected function sendPreview(): void
    {
        $messageText = $this->generateText();
        $this->chat->storage()->set('data.messageText', $messageText);

        $response = $this->chat->markdown($messageText)
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
        $channel = TelegraphChat::where('chat_id', setting('channel.id'))->latest()->first();
        $data = $this->chat->storage()->get('data.link_data');
        $msg = $channel->markdown($text);

        if (isset($data['image']) && strlen($data['image']) > 1) {
            $msg = $msg->photo($data['image']);
        }

        $msg->keyboard($this->channelKeyboard())
            ->withoutPreview()
            ->send();
    }

    /**
     * Inline keyboard attached to the channel post. Every button goes on its
     * own row.
     */
    protected function channelKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('Read Article')->url(
                $this->chat->storage()->get('data.url_short')
            ),
        ]);
    }

    protected function timeRead($url): int
    {
        //        $client = new Client();
        //        $crawler = $client->request('GET', $url);
        //
        //        $text = $crawler->filter('p')->each(function ($node) {
        //            return $node->text();
        //        });
        //
        //        return round(count(explode(' ', implode("\n", $text))) / 160);

        return 5;
    }

    protected function getOpenGraph()
    {
        $og = new OpenGraph;
        $ogData = $og->fetch($this->chat->storage()->get('data.url'));

        $data = [
            'title'       => $ogData['title'] ?? '',
            'description' => $ogData['description'] ?? '',
            'image'       => $ogData['image'] ?? '',
        ];

        $this->chat->storage()->set('data.link_data', $data);
    }

    protected function generateText(): string
    {
        $data = $this->chat->storage()->get('data.link_data');

        return '*'.$data['title']."*\n\n".
//            '_'.$this->timeRead($this->chat->storage()->get('data.url'))." min read_\n\n".
            ''.$data['description']."\n\n".
            '`'.$this->chat->storage()->get('data.url_short')."`\n".
            setting('channel.username');
    }

    protected function doesUrlRedirect($url): bool
    {
        $headers = get_headers($url, 1);
        if (! empty($headers['Location'])) {
            return true;
        } else {
            return false;
        }
    }
}
