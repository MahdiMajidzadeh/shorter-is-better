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
    public function handleStep1()
    {
        $this->chat->message('send your url')->send();
        $this->nextStep();
    }

    public function handleStep2()
    {
        $this->chat->storage()->set('data.url', $this->message);

        if (! $this->isUrl($this->message)) {
            $this->chat->message('url is not valid')->send();

            return;
        } elseif ($this->doesUrlRedirect($this->message)) {
            $this->chat->message('url is redirected, send original url')->send();

            return;
        }

        $this->chat->storage()->set('data.url_short', $this->makeShort($this->message));
        $this->getOpenGraph();
        $messageText = $this->generateText();
        $this->chat->storage()->set('data.messageText', $messageText);

        $response = $this->chat->markdown($messageText)
            ->keyboard(Keyboard::make()->buttons([
                Button::make('Confirm')->action('keyboard_handler')->param('action_name', 'channel_confirm'),
                Button::make('Edit')->action('keyboard_handler')->param('action_name', 'channel_edit'),
                Button::make('Dismiss')->action('keyboard_handler')->param('action_name', 'channel_dismiss'),
            ]))->send();

        $this->chat->storage()->set('data.last_id', $response->telegraphMessageId());
        $this->nextStep();
    }

    public function handleStep3()
    {
        $action = str_replace('channel_', '', $this->message);
        $funcName = 'handleStep3'.$action;
        $this->$funcName();

        $this->chat->deleteKeyboard(
            $this->chat->storage()->get('data.last_id')
        )->send();
    }

    public function handleStep3confirm()
    {
        $channel = TelegraphChat::where('chat_id', setting('channel.id'))->latest()->first();
        $data = $this->chat->storage()->get('data.link_data');

        $msg = $channel->markdown(
            $this->chat->storage()->get('data.messageText')
        );

        if (isset($data['image']) && strlen($data['image']) > 1) {
            $msg = $msg->photo($data['image']);
        }

        $msg->keyboard(Keyboard::make()->buttons([
            Button::make('Read Article')->url(
                $this->chat->storage()->get('data.url_short')
            ),
        ]))
            ->withoutPreview()
            ->send();

        $this->done();
    }

    public function handleStep3edit()
    {
        $this->chat->markdown('sent your replacement text:')->send();
        $this->nextStep();
    }

    public function handleStep3dismiss()
    {
        $this->chat->markdown('ok')->send();
        $this->done();
    }

    public function handleStep4()
    {
        $channel = TelegraphChat::where('chat_id', setting('channel.id'))->latest()->first();
        $data = $this->chat->storage()->get('data.link_data');
        $msg = $channel->markdown($this->message);

        if (isset($data['image']) && strlen($data['image']) > 1) {
            $msg = $msg->photo($data['image']);
        }

        $msg->keyboard(Keyboard::make()->buttons([
            Button::make('Read Article')->url(
                $this->chat->storage()->get('data.url_short')
            ),
        ]))
            ->withoutPreview()
            ->send();

        $this->done();
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

    private function getOpenGraph()
    {
        $og = new OpenGraph();
        $ogData = $og->fetch($this->chat->storage()->get('data.url'));

        $data = [
            'title'       => $ogData['title'] ?? '',
            'description' => $ogData['description'] ?? '',
            'image'       => $ogData['image'] ?? '',
        ];

        $this->chat->storage()->set('data.link_data', $data);
    }

    private function generateText(): string
    {
        $data = $this->chat->storage()->get('data.link_data');

        return '*'.$data['title']."*\n\n".
//            '_'.$this->timeRead($this->chat->storage()->get('data.url'))." min read_\n\n".
            ''.$data['description']."\n\n".
            '`'.$this->chat->storage()->get('data.url_short')."`\n".
            setting('channel.username');
    }

    private function doesUrlRedirect($url): bool
    {
        $headers = get_headers($url, 1);
        if (! empty($headers['Location'])) {
            return true;
        } else {
            return false;
        }
    }
}
