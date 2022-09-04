<?php

namespace App\Http\Webhooks\State;

use Goutte\Client;
use shweshi\OpenGraph\OpenGraph;
use App\Http\Webhooks\StateManager;
use AshAllenDesign\ShortURL\Facades\ShortURL;

class ForChannel extends StateManager
{
    public function handle($step, $text = null)
    {
        if ($step == 1) {
            $this->chat->message('send your url')->send();
        } elseif ($step == 2) {
            $this->inputs['url'] = $text;
            if ($this->isUrl($text)) {
                $og = new OpenGraph();
                $data = $og->fetch($this->inputs['url']);
                $short = $this->makeShort($this->inputs['url']);

                $this->chat->message(
                    $data['title']."\n\n".
                    $this->timeRead($this->inputs['url'])." min read\n\n".
                    $short."\n\n".
                    setting('channel.username')
                )->send();

                $this->lastStep = true;
            }
        }
    }

    protected function makeShort($url)
    {
        $shortURLObject = ShortURL::destinationUrl($url);
        $shorted = $shortURLObject->make();

        return $shorted['default_short_url'];
    }

    protected function timeRead($url): int
    {
        $client = new Client();
        $crawler = $client->request('GET', 'https://productify.substack.com/p/14-spotify-music-as-an-algorithm');

        $text = $crawler->filter('p')->each(function ($node) {
            return $node->text();
        });

        return round(count(explode(' ', implode("\n", $text))) / 180);
    }
}
