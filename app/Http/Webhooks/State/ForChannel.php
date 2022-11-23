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
    public function handle($step, $text = null)
    {
        if ($step == 1) {
            $this->chat->message('send your url')->send();
        } else if ($step == 2) {
            $this->inputs['url'] = $text;
            if ($this->isUrl($text)) {
                $og    = new OpenGraph();
                $data  = $og->fetch($this->inputs['url']);
                $short = $this->makeShort($this->inputs['url']);

                $messageText =  "*" . $data['title'] . "*\n\n" .
                    "_" . $this->timeRead($this->inputs['url']) . " min read_\n\n" .
                    "`" . $short . "`\n\n" .
                    "" . $data['description'] . "\n\n" .
                    setting('channel.username');

                $msg = $this->chat->markdown($messageText )->keyboard(Keyboard::make()->buttons([
                    Button::make('Read Article')->url($short),
                ]));

                if(isset($data['image'])){
                    $msg = $msg->photo($data['image']);
                }

                $response = $msg->send();

                $this->chat->replaceKeyboard(
                    messageId: $response->telegraphMessageId(),
                    newKeyboard: Keyboard::make()->buttons([
                    Button::make('open')->url('https://test.it/' . $response->telegraphMessageId()),
                ])
                )->send();
            }
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
}
