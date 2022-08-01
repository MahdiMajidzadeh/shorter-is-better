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

        $views = ShortURLVisit::query()
            ->select(DB::raw('Date(visited_at) as date'), DB::raw('count(*) as views'))
            ->where('visited_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $text = '';

        foreach ($views as $view) {
            $text .= $view->date.': '.$view->views."\n";
        }

        $this->chat->message($text)->send();
    }
}