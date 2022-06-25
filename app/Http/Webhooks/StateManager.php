<?php

namespace App\Http\Webhooks;

use Exception;
use AshAllenDesign\ShortURL\Facades\ShortURL;

class StateManager
{
    public $chat;
    public $inputs;
    public $lastStep = false;

    public function __construct($chat, $inputs)
    {
        $this->chat   = $chat;
        $this->inputs = $inputs;
    }

    public function shortURLWithKey($step, $text)
    {
        if ($step == 1) {
            $this->chat->message('send your url')->send();
        } else if ($step == 2) {
            // url validation
            $this->inputs['url'] = $text;
            $this->chat->message('send your key')->send();
        } else if ($step == 3) {
            $this->inputs['key'] = $text;
            $this->_makeShort($this->inputs['url'], $this->inputs['key']); // get for send message
            $this->lastStep = true;
        }
    }

    protected function _makeShort($url, $key = null)
    {
        $shortURLObject = ShortURL::destinationUrl($url);

        if (!is_null($key)) {
            $shortURLObject->urlKey($key);
        }

        try {
            $shorted = $shortURLObject->make();
        } catch (Exception $e) {
            return false;
        }

        $this->chat->message($shorted['default_short_url'])->send();

        return true;
    }

    public function shortURL($step, $text)
    {
        if ($step == 1) {
            $this->chat->message('send your url')->send();
        } else if ($step == 2) {
            $this->inputs['url'] = $text;
            $this->_makeShort($this->inputs['url']); // get for send message
            $this->lastStep = true;
        }
    }
}