<?php

namespace App\Http\Webhooks;

use Illuminate\Support\Str;
use App\Http\Webhooks\State\Stat;
use App\Http\Webhooks\State\Short;
use Illuminate\Support\Stringable;
use App\Http\Webhooks\State\Report;
use App\Http\Webhooks\State\ForChannel;
use App\Http\Webhooks\State\ShortWithKey;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\Handlers\WebhookHandler;

class Handler extends WebhookHandler
{
    public function stat()
    {
        $this->startState(Stat::class);
    }

    private function startState($class)
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $this->chat->storage()->set('state', $class);
        $this->chat->storage()->set('step', 1);

        $state = new $class($this->chat, $this->message);
        $state->handle();
    }

    public function isAuthenticated(): bool
    {
        $chat = TelegraphChat::query()->where('chat_id', $this->chat['chat_id'])->first();
        //  todo: involve bot
        if (!$chat || is_null($chat->user_id)) {
            $this->chat->message("Not Allowed \nPlease Authenticate with valid token\nUse /start to take a token")->send();

            return false;
        }
        return true;
    }

    public function short_key()
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

    public function for_channel()
    {
        $this->startState(ForChannel::class);
    }

    public function keyboard_handler()
    {
        $this->_cache();

        $this->inputs['action'] = $this->data->get('call', 'confirm');

        $fun = $this->state;

        if (!is_null($fun)) {
            $state = new $this->state($this->chat, $this->inputs);
            $state->handle($this->step, '');
            $this->inputs = $state->inputs;

            $this->_cacheUpdate($state->lastStep);
        }
    }

    public function start()
    {
        $chat = TelegraphChat::query()->where('chat_id', $this->chat['chat_id'])->first();
        //  todo: involve bot

        if (strlen($chat->hash) == 0) {
            $chat->hash = Str::random(50);
            $chat->save();
        }

        $this->chat->message('Please open this url:')->send();
        $this->chat->message(url('/auth/bot/' . $chat->hash))->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $class = $this->chat->storage()->get('state');

        if (is_null($class)) {
            $this->chat->storage()->set('state', Short::class);
            $this->chat->storage()->set('step', 2);
            $class = Short::class;
        }

        $state = new $class($this->chat, $this->message);
        $state->handle();
    }
}
