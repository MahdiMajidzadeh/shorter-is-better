<?php

namespace App\Http\Webhooks\State;

use Exception;
use App\Http\Webhooks\StateManager;
use AshAllenDesign\ShortURL\Facades\ShortURL;

class Short extends StateManager
{
    public function handle($step, $text = null)
    {
        if ($step == 1) {
            $this->chat->message('send your url')->send();
        } elseif ($step == 2) {
            $this->inputs['url'] = $text;
            if($this->isUrl($text)) {
                $this->_makeShort($this->inputs['url']); // get for send message
                $this->lastStep = true;
            }
        }
    }

    protected function _makeShort($url, $key = null)
    {
        $shortURLObject = ShortURL::destinationUrl($url);

        if (! is_null($key)) {
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
}