<?php

namespace App\Http\Webhooks\State;

use App\Http\Webhooks\StateManager;

class ShortWithKey extends StateManager
{
    public function handleStep1()
    {
        $this->chat->message('send your url')->send();
        $this->nextStep();
    }

    public function handleStep2()
    {
        if ($this->isUrl($text)) {
            $this->chat->storage()->set('data.url', $this->message);
            $this->chat->message('short key:')->send();

            $this->nextStep();
        } else {
            $this->chat->message('url is not valid')->send();
        }
    }

    public function handleStep3()
    {
        $url = $this->makeShort($this->chat->storage()->get('data.url'), $this->message);
        $this->chat->message($url)->send();
        $this->done();
    }
}
