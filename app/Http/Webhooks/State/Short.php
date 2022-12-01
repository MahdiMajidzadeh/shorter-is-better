<?php

namespace App\Http\Webhooks\State;

use App\Http\Webhooks\StateManager;

class Short extends StateManager
{
    public function handleStep1()
    {
        $this->chat->message('send your url')->send();
        $this->nextStep();
    }

    public function handleStep2()
    {
        if ($this->isUrl($this->message)) {
            $url = $this->makeShort($this->message);
            $this->chat->message($url)->send();
            $this->done();
        } else {
            $this->chat->message('url is not valid')->send();
        }
    }
}
