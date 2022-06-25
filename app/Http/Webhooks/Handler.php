<?php

namespace App\Http\Webhooks;

use Illuminate\Support\Stringable;
use Illuminate\Support\Facades\Cache;
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
//        $chat = TelegraphChat::where('chat_id',  $this->chat['chat_id'])->first();
//        if(!$chat){
//            $this->chat->message("Not Allowed \nPlease Authenticate with valid token")->send();
//            return false;
//        }

//        $this->chat->message(Auth::)->send();

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

        $state = new StateManager($this->chat, $this->inputs);
        $state->shortURLWithKey($this->step, $this->message);
        $this->inputs = $state->inputs;
        $this->_cacheUpdate($state->lastStep);
    }

    public function _cacheUpdate($lastStep)
    {
//        $this->chat->message(json_encode([$lastStep, $this->state, $this->step, $this->inputs]))->send();
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

        $state = new StateManager($this->chat, $this->inputs);
        $state->shortURL($this->step, $this->message);
        $this->inputs = $state->inputs;
        $this->_cacheUpdate($state->lastStep);
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $this->_cache();
        $fun = $this->state;

        $state = new StateManager($this->chat, $this->inputs);

        if (!is_null($fun)) {
            $state->$fun($this->step, $text);
            $this->inputs = $state->inputs;
        } else {
            $this->step = 2;
            $state->shortURL($this->step, $text);
        }

        $this->_cacheUpdate($state->lastStep);
    }
}
