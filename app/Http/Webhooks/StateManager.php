<?php

namespace App\Http\Webhooks;

use Exception;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Models\TelegraphChat;
use AshAllenDesign\ShortURL\Facades\ShortURL;

class StateManager
{
    public TelegraphChat $chat;
    public string $message;
    public int $step;

    public function __construct($chat, $message)
    {
        $this->chat    = $chat;
        $this->message = $message;
        $this->step    = $chat->storage()->get('step');
    }

    public function handle()
    {
        $funcName = "handleStep" . $this->step;
        $this->$funcName();
    }

    protected function isUrl($url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    protected function makeShort($url, $key = null): string
    {
        $shortURLObject = ShortURL::destinationUrl($url);

        if (!is_null($key)) {
            $shortURLObject->urlKey($key);
        }

        try {
            $shorted = $shortURLObject->make();
        } catch (Exception $e) {
            $shorted['default_short_url'] = '_not_valid_url';
        }

        return $shorted['default_short_url'];
    }

    protected function nextStep()
    {
        $this->chat->storage()->set(
            'step',
            $this->chat->storage()->get('step') + 1
        );
    }

    protected function done()
    {
        $this->chat->storage()->forget('state');
        $this->chat->storage()->forget('step');
        $this->chat->storage()->forget('data.*');
    }
}
