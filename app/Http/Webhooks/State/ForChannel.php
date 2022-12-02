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
    }

    public function handleStep2()
    {
        $this->chat->storage()->set('data.url', $this->message);

        if (!$this->isUrl($this->message)) {
            $this->chat->message('url is not valid')->send();
            return;
        }

        $this->chat->storage()->set('data.url_short', $this->makeShort($this->message));
        $this->getOpenGraph();
        $messageText = $this->generateText();
        $this->chat->storage()->set('data.messageText', $messageText);

        $msg      = $this->chat->markdown($messageText);
        $response = $msg->send();

        $this->chat->storage()->set('data.last_id', $response->telegraphMessageId());

        $this->chat->replaceKeyboard(
            messageId: $response->telegraphMessageId(),
            newKeyboard: Keyboard::make()->buttons([
            Button::make('Confirm')->action('keyboard_handler')->param('call', 'confirm'),
            Button::make('Edit')->action('keyboard_handler')->param('call', 'edit'),
            Button::make('Dismiss')->action('keyboard_handler')->param('call', 'dismiss'),
        ])
        )->send();
    }

    private function getOpenGraph()
    {
        $og     = new OpenGraph();
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

        return ("*" . $data['title'] . "*\n\n" .
            "_" . $this->timeRead($this->chat->storage()->get('data.url')) . " min read_\n\n" .
            "" . $data['description'] . "\n\n" .
            "`" . $this->chat->storage()->get('data.url_short') . "`\n" .
            setting('channel.username'));
    }

    protected function timeRead($url): int
    {
        $client  = new Client();
        $crawler = $client->request('GET', $url);

        $text = $crawler->filter('p')->each(function($node) {
            return $node->text();
        });

        return round(count(explode(' ', implode("\n", $text))) / 160);
    }

    public function handleStep3()
    {
        if ($this->inputs['action'] == 'confirm') {
            $channel = TelegraphChat::where('chat_id', setting('channel.id'))->latest()->first();

            $msg = $channel->markdown($this->inputs['temp_msg']);

            if (isset($this->inputs['temp_image'])) {
                $msg = $msg->photo($this->inputs['temp_image']);
            }

            $msg->keyboard(Keyboard::make()->buttons([
                Button::make('Read Article')->url($this->inputs['temp_url']),
            ]))->send();

            $this->lastStep = true;
        } else if ($this->inputs['action'] == 'edit') {
            $this->chat->markdown('sent your replacement text:')->send();
        } else if ($this->inputs['action'] == 'dismiss') {
            $this->chat->markdown('ok')->send();
            $this->lastStep = true;
        }

        $this->chat->deleteKeyboard($this->inputs['tg_id'])->send();
    }

    public function handleStep4()
    {
        $channel = TelegraphChat::where('chat_id', setting('channel.id'))->latest()->first();

        $msg = $channel->markdown($this->text);

        if (isset($this->inputs['temp_image'])) {
            $msg = $msg->photo($this->inputs['temp_image']);
        }

        $msg->keyboard(Keyboard::make()->buttons([
            Button::make('Read Article')->url($this->inputs['temp_url']),
        ]))->send();

        $this->done();
    }
}
