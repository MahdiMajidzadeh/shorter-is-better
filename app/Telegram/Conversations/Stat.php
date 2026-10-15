<?php

namespace App\Telegram\Conversations;

use App\Models\Link;
use SergiX44\Nutgram\Nutgram;
use App\Services\ReportService;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

/**
 * /stat: clicks by day (14 days), browser, device and OS for one link of
 * the active project. Accepts the short url or just its key.
 */
class Stat extends Conversation
{
    use InteractsWithBotChat;

    public function start(Nutgram $bot): void
    {
        $bot->sendMessage(__('bot_replies.send_short_url'));
        $this->next('receive');
    }

    public function receive(Nutgram $bot): void
    {
        $input = trim($bot->message()?->text ?? '');
        $key = basename(rtrim((string) parse_url($input, PHP_URL_PATH) ?: $input, '/'));

        $link = Link::forProject($this->botProject($bot))->where('url_key', $key)->first();

        if (! $link) {
            $bot->sendMessage(__('bot_replies.link_not_found'));

            return;
        }

        $stats = app(ReportService::class)->linkStats($link, 14);

        $lines = [
            '<b>'.e($link->displayTitle()).'</b>',
            e($link->default_short_url),
            e(__('bot_replies.clicks', ['count' => local_number($stats['clicks_total'])])),
            '',
            '<b>'.e(__('bot_replies.stat_daily')).'</b>',
        ];

        foreach ($stats['daily'] as $row) {
            $lines[] = e(local_date($row['date'])).': '.e(local_number($row['clicks']));
        }

        foreach (['browsers' => 'bot_replies.stat_browser', 'devices' => 'bot_replies.stat_device', 'operating_systems' => 'bot_replies.stat_os'] as $group => $title) {
            $lines[] = '';
            $lines[] = '<b>'.e(__($title)).'</b>';

            foreach ($stats[$group] as $row) {
                $name = $row['name'] ?: __('Unknown');
                $lines[] = e($group === 'devices' && $row['name'] ? __('device.'.$row['name']) : $name).': '.e(local_number($row['clicks']));
            }
        }

        $bot->sendMessage(implode("\n", $lines), parse_mode: ParseMode::HTML);
        $this->end();
    }
}
