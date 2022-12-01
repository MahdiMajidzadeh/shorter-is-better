<?php

namespace App\Http\Webhooks\State;

use Illuminate\Support\Facades\DB;
use App\Http\Webhooks\StateManager;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;

class Report extends StateManager
{
    public function handleStep1()
    {
        $this->chat->message($this->urlList())->send();
        $this->chat->message($this->nonBotData())->send();
        
        $this->done();
    }

    private function urlList(): string
    {
        $urls = ShortURLVisit::query()
            ->join('short_urls', 'short_urls.id', '=', 'short_url_visits.short_url_id')
            ->where('visited_at', '>=', now()->subDays(7))
            ->whereNot('device_type', 'robot')
            ->groupBy('short_url_id', 'destination_url', 'default_short_url')
            ->orderBy('views', 'desc')
            ->take(20)
            ->get(['short_url_id', 'destination_url', 'default_short_url', DB::raw('COUNT(*) as views')]);

        $text = "7 Days Top Url:\n\n";

        foreach ($urls as $url) {
            $text .= "original: ".$url->destination_url . "\nshort: " . $url->default_short_url . "\nviews: " . $url->views . "\n\n";
        }

        return $text;
    }

    private function nonBotData(): string
    {
        $views = ShortURLVisit::query()
            ->select(DB::raw('Date(visited_at) as date'), DB::raw('count(*) as views'))
            ->where('visited_at', '>=', now()->subDays(7))
            ->whereNot('device_type', 'robot')
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $text = "Non Bot Data:\n\n";

        foreach ($views as $view) {
            $text .= $view->date . ': ' . $view->views . "\n";
        }

        return $text;
    }

}
