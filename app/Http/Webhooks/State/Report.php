<?php

namespace App\Http\Webhooks\State;

use Illuminate\Support\Facades\DB;
use App\Http\Webhooks\StateManager;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;

class Report extends StateManager
{
    public function handle($step, $text = null)
    {
        $this->lastStep = true;

        $this->chat->message($this->allData())->send();
        $this->chat->message($this->nonBotData())->send();
        $this->chat->message($this->urlList())->send();
    }

    private function allData(): string
    {
        $views = ShortURLVisit::query()
            ->select(DB::raw('Date(visited_at) as date'), DB::raw('count(*) as views'))
            ->where('visited_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $text = "All Data:\n\n";

        foreach ($views as $view) {
            $text .= $view->date.': '.$view->views."\n";
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
            $text .= $view->date.': '.$view->views."\n";
        }

        return $text;
    }

    private function urlList(): string
    {
        $urls = ShortURLVisit::query()
            ->with(['shortURL'])
            ->where('visited_at', '>=', now()->subDays(7))
            ->whereNot('device_type', 'robot')
            ->get([DB::raw('COUNT(*) as views'), 'url']);

        $text = "7 Days Top Url:\n\n";

        foreach ($urls as $url) {
            $text .= $url->url.': '.$url->views."\n";
        }

        return $text;
    }
}
