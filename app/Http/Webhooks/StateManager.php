<?php

namespace App\Http\Webhooks;

use DefStudio\Telegraph\Models\TelegraphChat;

class StateManager
{
    public TelegraphChat $chat;
    public string $message;
    public int $step;

    public function __construct($chat,$message)
    {
        $this->chat = $chat;
        $this->message = $message;
        $this->step = $chat->storage()->get('step');
    }

    protected function isUrl($url) : bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->chat->message('URL not valid')->send();

            return false;
        }

        return true;
    }
}
