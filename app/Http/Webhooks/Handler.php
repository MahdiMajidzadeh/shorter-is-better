<?php

namespace App\Http\Webhooks;

use Illuminate\Support\Str;
use App\Http\Webhooks\State\Stat;
use App\Http\Webhooks\State\Short;
use Illuminate\Support\Stringable;
use App\Http\Webhooks\State\Report;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Webhooks\State\ForChannel;
use App\Http\Webhooks\State\ShortWithKey;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\Handlers\WebhookHandler;

class Handler extends WebhookHandler
{
    public $state = null;

    public $step = null;

    public $cacheKey = null;

    public $inputs = [];

    public function stat()
    {
        $this->startState(Stat::class);
    }

    public function shortkey()
    {
        $this->startState(ShortWithKey::class);
    }

    public function short()
    {
        $this->startState(Short::class);
    }

    public function report()
    {
        $this->startState(Report::class);
    }

    public function forchannel()
    {
        $this->startState(ForChannel::class);
    }

    public function keyboard_handler()
    {
        $this->_cache();

        $this->inputs['action'] = $this->data->get('call','confirm');

        $fun = $this->state;

        if (! is_null($fun)) {
            $state = new $this->state($this->chat, $this->inputs);
            $state->handle($this->step, '');
            $this->inputs = $state->inputs;

            $this->_cacheUpdate($state->lastStep);
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

    protected function handleChatMessage(Stringable $text): void
    {
        $token = $this->_cache();

        if (! $token) {
            return;
        }
        $fun = $this->state;

        if (! is_null($fun)) {
            $state = new $this->state($this->chat, $this->inputs);
            $state->handle($this->step, $text);
            $this->inputs = $state->inputs;
        } else {
            $this->step = 2;
            $state = new Short($this->chat, $this->inputs);
            $state->handle($this->step, $text);
        }

        $this->_cacheUpdate($state->lastStep);
    }

    public function start()
    {
        $chat = TelegraphChat::where('chat_id', $this->chat['chat_id'])->first();

        if (strlen($chat->hash) == 0) {
            $chat->hash = Str::random(50);
            $chat->save();
        }

        $this->chat->message('Please open this url:')->send();
        $this->chat->message(url('/auth/bot/'.$chat->hash))->send();
    }

    private function startState($class)
    {
        $token = $this->_cache([
            'state'  => $class,
            'step'   => 1,
            'inputs' => [],
        ]);

        if (! $token) {
            return;
        }

        $state = new $class($this->chat, $this->inputs);
        $state->handle($this->step, $this->message);
        $this->inputs = $state->inputs;

        $this->_cacheUpdate($state->lastStep);
    }
}
