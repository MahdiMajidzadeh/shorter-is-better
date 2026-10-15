<?php

namespace App\Http\Webhooks\State;

use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use App\Http\Webhooks\StateManager;

class Report extends StateManager
{
    public function handleStep1()
    {
        if (! $this->project()) {
            $this->chat->message('you have no project')->send();
            $this->done();

            return;
        }

        $this->chat->message($this->urlList())->send();
        $this->chat->message($this->nonBotData())->send();

        $this->done();
    }

    private function urlList(): string
    {
        $urls = Visit::query()
            ->forProject($this->project())
            ->join('short_urls', 'short_urls.id', '=', 'short_url_visits.short_url_id')
            ->where('visited_at', '>=', now()->subDays(7))
            ->human()
            ->groupBy('short_url_id', 'destination_url', 'default_short_url')
            ->orderBy('views', 'desc')
            ->take(20)
            ->get(['short_url_id', 'destination_url', 'default_short_url', DB::raw('COUNT(*) as views')]);

        $text = "7 Days Top Url:\n\n";

        foreach ($urls as $url) {
            $text .= 'original: '.$url->destination_url."\nshort: ".$url->default_short_url."\nviews: ".$url->views."\n\n";
        }

        return $text;
    }

    private function nonBotData(): string
    {
        $views = Visit::query()
            ->forProject($this->project())
            ->select(DB::raw('Date(visited_at) as date'), DB::raw('count(*) as views'))
            ->where('visited_at', '>=', now()->subDays(7))
            ->human()
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $text = "Non Bot Data:\n\n";

        foreach ($views as $view) {
            $text .= $view->date.': '.$view->views."\n";
        }

        return $text;
    }
}
