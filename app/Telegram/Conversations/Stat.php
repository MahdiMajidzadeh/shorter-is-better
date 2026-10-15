<?php

namespace App\Telegram\Conversations;

use App\Models\Link;
use App\Models\Visit;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\DB;
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

        $lines = [
            '<b>'.e($link->displayTitle()).'</b>',
            e($link->default_short_url),
            e(__('bot_replies.clicks', ['count' => local_number($link->visits()->human()->count())])),
            '',
            '<b>'.e(__('bot_replies.stat_daily')).'</b>',
        ];

        $daily = Visit::query()
            ->where('short_url_id', $link->id)
            ->human()
            ->select(DB::raw('Date(visited_at) as day'), DB::raw('count(*) as views'))
            ->where('visited_at', '>=', now()->subDays(14))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->orderBy('day')
            ->get();

        foreach ($daily as $row) {
            $lines[] = e(local_date($row->day)).': '.e(local_number($row->views));
        }

        foreach (['browser' => 'telegram.stat_browser', 'device_type' => 'telegram.stat_device', 'operating_system' => 'telegram.stat_os'] as $column => $title) {
            $lines[] = '';
            $lines[] = '<b>'.e(__($title)).'</b>';

            $rows = Visit::query()
                ->where('short_url_id', $link->id)
                ->human()
                ->groupBy($column)
                ->selectRaw($column.' as name, count(*) as views')
                ->orderByDesc('views')
                ->get();

            foreach ($rows as $row) {
                $name = $row->name ?: __('Unknown');
                $lines[] = e($column === 'device_type' && $row->name ? __('device.'.$row->name) : $name).': '.e(local_number($row->views));
            }
        }

        $bot->sendMessage(implode("\n", $lines), parse_mode: ParseMode::HTML);
        $this->end();
    }
}
