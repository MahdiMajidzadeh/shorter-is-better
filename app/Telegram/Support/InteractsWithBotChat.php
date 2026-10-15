<?php

namespace App\Telegram\Support;

use App\Models\User;
use App\Models\BotChat;
use App\Models\Project;
use SergiX44\Nutgram\Nutgram;

/**
 * Shared accessors for handlers and conversation steps. The middleware has
 * already resolved the chat, its user and its project for this update.
 */
trait InteractsWithBotChat
{
    protected function botChat(Nutgram $bot): BotChat
    {
        return $bot->get('botChat');
    }

    protected function botUser(Nutgram $bot): User
    {
        return $this->botChat($bot)->user;
    }

    protected function botProject(Nutgram $bot): Project
    {
        return $this->botChat($bot)->activeProject();
    }
}
