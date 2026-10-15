<?php

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use App\Services\ReportService;
use App\Telegram\Support\InteractsWithBotChat;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Message\LinkPreviewOptions;

/**
 * /report: the project's top links and daily human clicks for 7 days.
 */
class ReportHandler
{
    use InteractsWithBotChat;

    public function __invoke(Nutgram $bot, ReportService $reports): void
    {
        $project = $this->botProject($bot);
        $summary = $reports->summary($project, 7);

        $lines = [
            '<b>'.e(__('bot_replies.report_title', ['project' => $project->name])).'</b>',
            e(__('bot_replies.clicks', ['count' => local_number($summary['clicks'])])).' · '.e(__('bot_replies.report_links_new', ['count' => local_number($summary['links_new'])])),
            '',
        ];

        if ($summary['top_links'] === []) {
            $lines[] = e(__('bot_replies.report_empty'));
        }

        foreach ($summary['top_links'] as $row) {
            $title = $row['title'] ?: (parse_url($row['destination_url'], PHP_URL_HOST) ?: $row['destination_url']);
            $lines[] = '• '.e($title);
            $lines[] = '  '.e($row['short_url']).' — '.e(__('bot_replies.clicks', ['count' => local_number($row['clicks'])]));
        }

        if ($summary['channels'] !== []) {
            $lines[] = '';
            $lines[] = '<b>'.e(__('bot_replies.report_channels')).'</b>';

            foreach ($summary['channels'] as $row) {
                $lines[] = e($row['name'] ?? __('Not posted to a channel')).': '.e(local_number($row['clicks']));
            }
        }

        if ($summary['daily'] !== []) {
            $lines[] = '';
            $lines[] = '<b>'.e(__('bot_replies.report_daily')).'</b>';

            foreach ($summary['daily'] as $row) {
                $lines[] = e(local_date($row['date'])).': '.e(local_number($row['clicks']));
            }
        }

        $bot->sendMessage(
            implode("\n", $lines),
            parse_mode: ParseMode::HTML,
            link_preview_options: LinkPreviewOptions::make(is_disabled: true),
        );
    }
}
