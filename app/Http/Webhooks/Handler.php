<?php

namespace App\Http\Webhooks;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    public $state = null;

    public $step = null;

    public $cacheKey = null;

    public $inputs = [];

    public function stat()
    {
        $token = $this->_cache([
            'state'  => 'statURL',
            'step'   => 1,
            'inputs' => [],
        ]);

        if (! $token) {
            return;
        }
    }

    public function _cache($data = null)
    {
        $chat = TelegraphChat::where('chat_id', $this->chat['chat_id'])->first();
        if (! $chat || is_null($chat->user_id)) {
            $this->chat->message("Not Allowed \nPlease Authenticate with valid token")->send();

            return false;
        }

        $this->cacheKey = 'tgs_'.$this->bot->id.'_'.$this->chat;

        if (is_null($data)) {
            $data = Cache::get($this->cacheKey);
        } else {
            Cache::put($this->cacheKey, $data, now()->addMinutes(10));
        }

        if (! is_null($data)) {
            $this->state = $data['state'];
            $this->step = $data['step'];
            $this->inputs = $data['inputs'];
        }

        return true;
    }

    public function shortkey()
    {
        $token = $this->_cache([
            'state'  => 'shortURLWithKey',
            'step'   => 1,
            'inputs' => [],
        ]);

        if (! $token) {
            return;
        }

        $state = new StateManager($this->chat, $this->inputs);
        $state->shortURLWithKey($this->step, $this->message);
        $this->inputs = $state->inputs;
        $this->_cacheUpdate($state->lastStep);
    }

    public function _cacheUpdate($lastStep)
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
        $token = $this->_cache([
            'state'  => 'shortURL',
            'step'   => 1,
            'inputs' => [],
        ]);

        if (! $token) {
            return;
        }

        $state = new StateManager($this->chat, $this->inputs);
        $state->shortURL($this->step, $this->message);
        $this->inputs = $state->inputs;
        $this->_cacheUpdate($state->lastStep);
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $token = $this->_cache();

        if (! $token) {
            return;
        }
        $fun = $this->state;

        $state = new StateManager($this->chat, $this->inputs);

        if (! is_null($fun)) {
            $state->$fun($this->step, $text);
            $this->inputs = $state->inputs;
        } else {
            $this->step = 2;
            $state->shortURL($this->step, $text);
        }

        $this->_cacheUpdate($state->lastStep);
    }

    public function auth()
    {
        $chat = TelegraphChat::where('chat_id', $this->chat['chat_id'])->first();

        if (! $chat) {
            $this->bot->chats()->create([
                'chat_id' => $this->chat['chat_id'],
                'name' => $this->chat['chat_id'],
            ]);

            $chat = TelegraphChat::where('chat_id', $this->chat['chat_id'])->first();
            $chat->hash = Str::random(50);
            $chat->save();
        }

        $this->chat->message('Please open this url:')->send();
        $this->chat->message(url('/auth/bot/'.$chat->hash))->send();
    }

    public function report()
    {
        $token = $this->_cache([
            'state'  => 'report',
            'step'   => 1,
            'inputs' => [],
        ]);

        if (! $token) {
            return;
        }

        $state = new StateManager($this->chat, $this->inputs);
        $state->report($this->step, $this->message);
        $this->inputs = $state->inputs;
        $this->_cacheUpdate($state->lastStep);
    }
}
