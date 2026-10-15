<?php

namespace App\Http\Webhooks\State;

use App\Models\Link;
use App\Models\Visit;
use AsciiTable\Builder;
use Illuminate\Support\Facades\DB;
use App\Http\Webhooks\StateManager;

class Stat extends StateManager
{
    public function handleStep1()
    {
        $this->chat->message('send shorted url')->send();
        $this->nextStep();
    }

    public function handleStep2()
    {
        $project = $this->project();
        $short = $project ? Link::forProject($project)->where('default_short_url', trim($this->message))->first() : null;

        if (! $short) {
            $this->chat->message('url not fount')->send();

            return;
        }

        $this->nonBotData($short->id);
        $this->visitSummaryData($short->id, 'browser', 'Browsers');
        $this->visitSummaryData($short->id, 'device_type', 'Device Type');
        $this->visitSummaryData($short->id, 'operating_system', 'Operating System');
        $this->done();
    }

    private function visitSummaryData($id, $dataType, $title)
    {
        $browserStat = Visit::where('short_url_id', $id)
            ->groupBy($dataType)
            ->selectRaw($dataType.' as name, count(*) as num')
            ->human()
            ->orderBy('num', 'desc')
            ->get();

        $this->sendStat($title, 'name', $browserStat);
    }

    private function nonBotData($id)
    {
        $views = Visit::query()
            ->select(DB::raw('Date(visited_at) as name'), DB::raw('count(*) as num'))
            ->where('visited_at', '>=', now()->subDays(14))
            ->where('short_url_id', $id)
            ->human()
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $this->sendStat('Views', 'date', $views);
    }

    private function sendStat($title, $row, $stats)
    {
        $builder = new Builder;
        $builder->setTitle($title);

        foreach ($stats as $stat) {
            $builder->addRow([
                $row => $stat->name,
                ' '  => $stat->num,
            ]);
        }

        $this->chat->markdown('```'.$builder->renderTable().'```')->send();
    }
}
