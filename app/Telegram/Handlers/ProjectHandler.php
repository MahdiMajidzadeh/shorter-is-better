<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * /project lists the projects the user may use; choosing one makes it the
 * chat's active project for every later command.
 */
class ProjectHandler
{
    use InteractsWithBotChat;

    public function list(Nutgram $bot): void
    {
        $chat = $this->botChat($bot);
        $current = $chat->activeProject();
        $keyboard = InlineKeyboardMarkup::make();

        foreach ($chat->user->accessibleProjectList() as $project) {
            $label = ($project->is($current) ? '✅ ' : '').$project->name.' · '.$project->domain;
            $keyboard->addRow(InlineKeyboardButton::make($label, callback_data: 'project:'.$project->id));
        }

        $bot->sendMessage(__('bot_replies.choose_project'), reply_markup: $keyboard);
    }

    public function choose(Nutgram $bot, string $id): void
    {
        $chat = $this->botChat($bot);
        $project = $chat->user->accessibleProjectList()->firstWhere('id', (int) $id);

        if (! $project) {
            $bot->answerCallbackQuery(text: __('bot_replies.project_not_allowed'), show_alert: true);

            return;
        }

        $chat->forceFill(['project_id' => $project->id])->save();

        $bot->answerCallbackQuery();
        $bot->editMessageText(
            text: __('bot_replies.project_switched', ['project' => $project->name, 'domain' => $project->domain]),
            chat_id: $bot->chatId(),
            message_id: $bot->messageId(),
        );
    }
}
