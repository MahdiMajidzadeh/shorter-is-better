<?php

namespace App\Http\Webhooks;

class StateManager
{
    public $chat;

    public $inputs;

    public $lastStep = false;

    public function __construct($chat, $inputs)
    {
        $this->chat = $chat;
        $this->inputs = $inputs;
    }

    protected function isUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->chat->message('URL not valid')->send();
            return false;
        }

        return true;
    }
}
