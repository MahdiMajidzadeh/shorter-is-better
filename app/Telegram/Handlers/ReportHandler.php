<?php

namespace App\Telegram\Handlers;

use App\Models\Visit;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\DB;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

/**
 * /report: the project's top links and daily human clicks for 7 days.
 */
class ReportHandler
{
    use InteractsWithBotChat;

    public function __invoke(Nutgram $bot): void
    {
        $project = $this->botProject($bot);

        $top = Visit::query()
            ->forProject($project)
            ->human()
            ->join('short_urls', 'short_urls.id', '=', 'short_url_visits.short_url_id')
            ->where('visited_at', '>=', now()->subDays(7))
            ->groupBy('short_url_id', 'title', 'destination_url', 'default_short_url')
            ->orderByDesc('views')
            ->take(20)
            ->get(['short_url_id', 'title', 'destination_url', 'default_short_url', DB::raw('COUNT(*) as views')]);

        $daily = Visit::query()
            ->forProject($project)
            ->human()
            ->select(DB::raw('Date(visited_at) as day'), DB::raw('count(*) as views'))
            ->where('visited_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->orderBy('day')
            ->get();

        $lines = ['<b>'.e(__('bot_replies.report_title', ['project' => $project->name])).'</b>', ''];

        if ($top->isEmpty()) {
            $lines[] = e(__('bot_replies.report_empty'));
        }

        foreach ($top as $row) {
            $title = $row->title ?: (parse_url($row->destination_url, PHP_URL_HOST) ?: $row->destination_url);
            $lines[] = '• '.e($title);
            $lines[] = '  '.e($row->default_short_url).' — '.e(__('bot_replies.clicks', ['count' => local_number($row->views)]));
        }

        if ($daily->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '<b>'.e(__('bot_replies.report_daily')).'</b>';

            foreach ($daily as $row) {
                $lines[] = e(local_date($row->day)).': '.e(local_number($row->views));
            }
        }

        $bot->sendMessage(
            implode("\n", $lines),
            parse_mode: ParseMode::HTML,
            link_preview_options: LinkPreviewOptions::make(is_disabled: true),
        );
    }
}
