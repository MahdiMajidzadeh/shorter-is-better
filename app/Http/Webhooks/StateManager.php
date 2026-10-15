<?php

namespace App\Http\Webhooks;

use App\Models\Link;
use App\Models\User;
use App\Models\Project;
use App\Services\Shortener;
use App\Exceptions\ShortenerException;
use DefStudio\Telegraph\Models\TelegraphChat;

class StateManager
{
    public TelegraphChat $chat;

    public string $message;

    public int $step;

    public function __construct($chat, $message)
    {
        $this->chat = $chat;
        $this->message = $message;
        $this->step = $chat->storage()->get('step');
    }

    public function handle()
    {
        $funcName = 'handleStep'.$this->step;
        $this->$funcName();
    }

    protected function isUrl($url): bool
    {
        return app(Shortener::class)->isValidUrl((string) $url);
    }

    protected function user(): ?User
    {
        return User::find($this->chat->user_id);
    }

    /**
     * The project this chat works in. Until chats carry a project of their
     * own, it is the first one the bound user can access.
     */
    protected function project(): ?Project
    {
        return $this->user()?->accessibleProjects()->first();
    }

    protected function makeShort($url, $key = null): string
    {
        $project = $this->project();

        if (! $project) {
            return '_no_project';
        }

        try {
            return app(Shortener::class)->make($project, $this->user(), $url, Link::SOURCE_BOT, $key)->default_short_url;
        } catch (ShortenerException $e) {
            return '_not_valid_url';
        }
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
        $this->chat->storage()->forget('data');
    }
}
