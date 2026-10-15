<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;

class PanelController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $project = $this->project();

        if (! Link::forProject($project)->exists()) {
            return view('panel.dashboard', ['hasLinks' => false]);
        }

        $summary = $reports->summary($project, 7);

        return view('panel.dashboard', [
            'hasLinks'     => true,
            'summary'      => $summary,
            'todayChange'  => $this->percentChange($summary['clicks_today'], $summary['clicks_yesterday']),
            'weekChange'   => $this->percentChange($summary['clicks'], $summary['clicks_previous']),
            'firstLinkAt'  => Carbon::parse(Link::forProject($project)->min('created_at')),
            'daily'        => $reports->daily($reports->visits($project), now()->subDays(30)),
            'recentVisits' => $reports->recent($project, 8),
        ]);
    }

    private function percentChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }
}
