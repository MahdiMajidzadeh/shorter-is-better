<?php

namespace App\Http\Webhooks\State;

use Goutte\Client;
use shweshi\OpenGraph\OpenGraph;
use App\Http\Webhooks\StateManager;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use DefStudio\Telegraph\Models\TelegraphChat;

class ForChannel extends StateManager
{
    public $text;

    /*
     *  input [
     *      url
     *      temp_msg
     *      temp_url
     *      temp_image
     *      tg_id
     */

    public function handle($step, $text = null)
    {
        $this->text = $text;
        $funcName   = "handleStep" . $step;
        $this->$funcName();
    }

    public function handleStep1()
    {
        $this->chat->message('send your url')->send();
    }

    public function handleStep2()
    {
        $this->inputs['url'] = $this->text;
        if ($this->isUrl($this->text)) {
            $og    = new OpenGraph();
            $data  = $og->fetch($this->inputs['url']);
            $short = $this->makeShort($this->inputs['url']);

            $messageText = "*" . $data['title'] . "*\n\n" .
                "_" . $this->timeRead($this->inputs['url']) . " min read_\n\n" .
                "" . $data['description'] . "\n\n" .
                "`" . $short . "`\n" .
                setting('channel.username');

            $this->inputs['temp_msg'] = $messageText;
            $this->inputs['temp_url'] = $short;

            $msg = $this->chat->markdown($messageText);

            if (isset($data['image'])) {
                $this->inputs['temp_image'] = $data['image'];
            }

            $response = $msg->send();

            $this->inputs['tg_id'] = $response->telegraphMessageId();

            $this->chat->replaceKeyboard(
                messageId: $response->telegraphMessageId(),
                newKeyboard: Keyboard::make()->buttons([
                Button::make('Confirm')->action('keyboard_handler')->param('call', 'confirm'),
                Button::make('Edit')->action('keyboard_handler')->param('call', 'edit'),
                Button::make('Dismiss')->action('keyboard_handler')->param('call', 'dismiss'),
            ])
            )->send();
        }
    }

    protected function makeShort($url)
    {
        $shortURLObject = ShortURL::destinationUrl($url);
        $shorted        = $shortURLObject->make();

        return $shorted['default_short_url'];
    }

    protected function timeRead($url): int
    {
        $client  = new Client();
        $crawler = $client->request('GET', $url);

        $text = $crawler->filter('p')->each(function($node) {
            return $node->text();
        });

        return round(count(explode(' ', implode("\n", $text))) / 180);
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

        $this->lastStep = true;
    }
}
