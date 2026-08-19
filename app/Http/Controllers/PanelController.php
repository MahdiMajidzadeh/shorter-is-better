<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
use AshAllenDesign\ShortURL\Models\ShortURL as ShortModel;

class PanelController extends Controller
{
    public function index(Request $request)
    {
        if (! ShortModel::exists()) {
            return view('panel.dashboard', ['hasLinks' => false]);
        }

        $clicksToday = $this->visits()->where('visited_at', '>=', today())->count();
        $clicksYesterday = $this->visits()->whereBetween('visited_at', [today()->subDay(), today()])->count();

        $clicksWeek = $this->visits()->where('visited_at', '>=', now()->subDays(7))->count();
        $clicksPrevWeek = $this->visits()->whereBetween('visited_at', [now()->subDays(14), now()->subDays(7)])->count();

        $topLinks = $this->visits()
            ->join('short_urls', 'short_urls.id', '=', 'short_url_visits.short_url_id')
            ->where('visited_at', '>=', now()->subDays(7))
            ->groupBy('short_url_id', 'url_key', 'default_short_url', 'destination_url')
            ->orderByDesc('views')
            ->take(5)
            ->get(['short_url_id', 'url_key', 'default_short_url', 'destination_url', DB::raw('COUNT(*) as views')]);

        return view('panel.dashboard', [
            'hasLinks'     => true,
            'clicksToday'  => $clicksToday,
            'todayChange'  => $this->percentChange($clicksToday, $clicksYesterday),
            'clicksWeek'   => $clicksWeek,
            'weekChange'   => $this->percentChange($clicksWeek, $clicksPrevWeek),
            'totalLinks'   => ShortModel::count(),
            'newLinks'     => ShortModel::where('created_at', '>=', now()->subDays(7))->count(),
            'totalClicks'  => $this->visits()->count(),
            'firstLinkAt'  => Carbon::parse(ShortModel::min('created_at')),
            'views'        => $this->dailyViews(),
            'topLinks'     => $topLinks,
            'referrers'    => $this->referrers(),
            'recentVisits' => $this->visits()->with('shortURL')->orderByDesc('visited_at')->take(8)->get(),
        ]);
    }

    private function visits(): Builder
    {
        return ShortURLVisit::query()->whereNot('device_type', 'robot');
    }

    private function dailyViews()
    {
        return $this->visits()
            ->select(DB::raw('Date(visited_at) as x'), DB::raw('count(*) as y'))
            ->where('visited_at', '>', now()->subDays(30))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->orderBy('x')
            ->get();
    }

    private function referrers()
    {
        return $this->visits()
            ->where('visited_at', '>=', now()->subDays(7))
            ->groupBy('referer_url')
            ->get(['referer_url', DB::raw('COUNT(*) as views')])
            ->groupBy(fn ($row) => $row->referer_url
                ? (parse_url($row->referer_url, PHP_URL_HOST) ?: $row->referer_url)
                : 'direct')
            ->map(fn ($rows, $host) => (object) ['host' => $host, 'views' => $rows->sum('views')])
            ->sortByDesc('views')
            ->take(5)
            ->values();
    }

    private function percentChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }
}
