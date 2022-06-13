<?php

namespace App\Http\Webhooks;

use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use DefStudio\Telegraph\Handlers\WebhookHandler;

class Handler extends WebhookHandler
{
    public $state = null;
    public $step = null;
    public $cacheKey = null;


    public function stat()
    {
    }

    public function shortkey()
    {
    }

    public function short()
    {
        $this->initCache();

        Cache::put($this->cacheKey, 'short|1', now()->addMinutes(5));
        $this->chat->message('send your url')->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $this->initCache();

        Log::debug(Cache::get($this->cacheKey));

        $this->checkState();

//        $text = $this->message->text();
//        $this->chat->message('hello')->send();
    }

    public function initCache()
    {
        $this->cacheKey = 'tgs_' . $this->bot->id . '_' . $this->chat;

        if (Cache::has($this->cacheKey)) {
            $data = Cache::get($this->cacheKey);
            [$this->state, $this->step] = explode('|', $data);
        }
    }

    public function checkState()
    {
        if($this->state == 'short'){
            Log::debug('short');
            $this->makeShort();
        }
    }

    public function makeShort()
    {
        // validate url
        $shorted = ShortURL::destinationUrl($this->message->text())->make();

        $this->chat->message($shorted['default_short_url'])->send();

        Cache::forget($this->cacheKey);
    }
}