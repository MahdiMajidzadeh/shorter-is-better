<?php

namespace App\Http\Webhooks\State;

use App\Http\Webhooks\StateManager;

class Stat extends StateManager
{
    public function handle($step, $text = null)
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
        $this->visitSummaryData($short->id, 'browser', 'Browsers');
        $this->visitSummaryData($short->id, 'device_type', 'Device Type');
        $this->visitSummaryData($short->id, 'operating_system','Operating System');
        $this->done();
    }

    private function visitSummaryData($id, $dataType, $title)
    {
        $browserStat = ShortURLVisit::where('short_url_id', $id)
            ->groupBy($dataType)
            ->selectRaw($dataType. ' as name, count(*) as num')
            ->whereNot('device_type', 'robot')
            ->orderBy('num', 'desc')
            ->get();

        $this->sendStat('Browsers', 'name', $browserStat);
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

        $this->sendStat('Views', 'date', $views);
    }

    private function sendStat($title, $row, $stats)
    {
        $builder = new Builder();
        $builder->setTitle($title);

        foreach ($stats as $stat) {
            $builder->addRow([
                $row => $stat->name,
                ' '  => $stat->num,
            ]);
        }

        $this->chat->markdown('```' . $builder->renderTable() . '```')->send();
    }
}
