<?php

namespace App\Http\Webhooks;

use Exception;
use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Cache;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use DefStudio\Telegraph\Handlers\WebhookHandler;

class Handler extends WebhookHandler
{
    public $state = null;
    public $step = null;
    public $cacheKey = null;
    public $inputs = [];

    public function stat()
    {
        $this->_cache([
            'state'  => 'statURL',
            'step'   => 1,
            'inputs' => [],
        ]);
    }

    public function _cache($data = null)
    {
        $this->cacheKey = 'tgs_' . $this->bot->id . '_' . $this->chat;

        if (is_null($data)) {
            $data = Cache::get($this->cacheKey);
        } else {
            Cache::put($this->cacheKey, $data, now()->addMinutes(10));
        }

        if (!is_null($data)) {
            $this->state  = $data['state'];
            $this->step   = $data['step'];
            $this->inputs = $data['inputs'];
        }
    }

    public function shortkey()
    {
        $this->_cache([
            'state'  => 'shortURLWithKey',
            'step'   => 1,
            'inputs' => [],
        ]);

        $this->shortURLWithKey('');
    }

    public function shortURLWithKey($text)
    {
        $lastStep = false;

        if ($this->step == 1) {
            $this->chat->message('send your url 2')->send();
        } else if ($this->step == 2) {
            // url validation
            $this->inputs['url'] = $text;
            $this->chat->message('send your key 3')->send();
        } else if ($this->step == 3) {
            $this->inputs['key'] = $text;
            $this->makeShort($this->inputs['url'], $this->inputs['key']); // get for send message
            $lastStep = true;
        }

        $this->_cacheUpdate($lastStep);
    }

    public function makeShort($url, $key = null)
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

    public function _cacheUpdate($lastStep = false)
    {
        if ($lastStep) {
            Cache::forget($this->cacheKey);
        } else {
            $this->step++;

            Cache::put($this->cacheKey, [
                'state'  => $this->state,
                'step'   => $this->step,
                'inputs' => $this->inputs,
            ], now()->addMinutes(10));
        }
    }

    public function short()
    {
        $this->_cache([
            'state'  => 'shortURL',
            'step'   => 1,
            'inputs' => [],
        ]);

        $this->shortURL('');
    }

    public function shortURL($text)
    {
        $lastStep = false;

        if ($this->step == 1) {
            $this->chat->message('send your url')->send();
        } else if ($this->step == 2) {
            $this->inputs['url'] = $text;
            $this->makeShort($this->inputs['url']); // get for send message
            $lastStep = true;
        }

        $this->_cacheUpdate($lastStep);
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $this->_cache();
        $fun = $this->state;

        if (!is_null($fun)) {
            $this->$fun($text);
        } else {
            $this->step = 2;
            $this->shortURL($text);
        }
    }
}
