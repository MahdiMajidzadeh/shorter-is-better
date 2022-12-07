<?php

namespace App\Http\Webhooks\State;

use Illuminate\Support\Facades\DB;
use App\Http\Webhooks\StateManager;
use AshAllenDesign\ShortURL\Models\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;

class Stat extends StateManager
{
    public function handleStep1()
    {
        $this->chat->message('send shorted url')->send();
        $this->nextStep();
    }

    public function handleStep2()
    {
        $short = ShortURL::query()->where('default_short_url', $this->message)->first();

        if (!$short) {
            $this->chat->message('url not fount')->send();
            return;
        }

        $this->nonBotData($short->id);
        $this->visitSummaryData($short->id);
        $this->done();
    }

    private function visitSummaryData($id)
    {
        $browserStat = ShortURLVisit::where('short_url_id', $id)
            ->groupBy('browser')
            ->selectRaw('browser as name, count(*) as num')
            ->orderBy('num', 'desc')
            ->get();

        $this->sendStat('Browsers', $browserStat);

        $deviceTypeStat = ShortURLVisit::where('short_url_id', $id)
            ->groupBy('device_type')
            ->selectRaw('device_type as name, count(*) as num')
            ->orderBy('num', 'desc')
            ->get();

        $this->sendStat('Device Type:', $deviceTypeStat);

        $operatingSystemStat = ShortURLVisit::where('short_url_id', $id)
            ->groupBy('operating_system')
            ->selectRaw('operating_system as name, count(*) as num')
            ->orderBy('num', 'desc')
            ->get();

        $this->sendStat('Operating System:', $operatingSystemStat);
    }

    private function nonBotData($id)
    {
        $views = ShortURLVisit::query()
            ->select(DB::raw('Date(visited_at) as name'), DB::raw('count(*) as num'))
            ->where('visited_at', '>=', now()->subDays(14))
            ->where('short_url_id', $id)
            ->whereNot('device_type', 'robot')
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $this->sendStat('Non Bot Data:', $views);
    }

    private function sendStat($title, $stats)
    {
        $text = $title . "\n\n";

        foreach ($stats as $stat) {
            $text .= $stat->name . ': ' . $stat->num . "\n";
        }

        $this->chat->message($text)->send();
    }
}
