<?php

namespace App\Services;

use App\Models\Link;
use App\Models\Visit;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every analytics query in one place, so the panel, the bot, the API and
 * the mini app agree on the numbers. Robots are always excluded.
 */
class ReportService
{
    public function summary(Project $project, int $days = 7): array
    {
        $since = now()->subDays($days);
        $previous = now()->subDays($days * 2);

        return [
            'days'            => $days,
            'since'           => $since->toIso8601String(),
            'clicks'          => $this->visits($project)->where('visited_at', '>=', $since)->count(),
            'clicks_previous' => $this->visits($project)->whereBetween('visited_at', [$previous, $since])->count(),
            'clicks_total'    => $this->visits($project)->count(),
            'links_total'     => Link::forProject($project)->count(),
            'links_new'       => Link::forProject($project)->where('created_at', '>=', $since)->count(),
            'daily'           => $this->daily($this->visits($project), $since),
            'top_links'       => $this->topLinks($project, $since),
            'devices'         => $this->breakdown($this->visits($project)->where('visited_at', '>=', $since), 'device_type'),
            'sources'         => $this->sources($project, $since),
            'referrers'       => $this->referrers($this->visits($project)->where('visited_at', '>=', $since)),
        ];
    }

    public function linkStats(Link $link, int $days = 30): array
    {
        $since = now()->subDays($days);
        $visits = fn () => Visit::query()->where('short_url_id', $link->id)->human();

        return [
            'days'              => $days,
            'clicks_total'      => $visits()->count(),
            'clicks'            => $visits()->where('visited_at', '>=', $since)->count(),
            'daily'             => $this->daily($visits(), $since),
            'browsers'          => $this->breakdown($visits(), 'browser'),
            'devices'           => $this->breakdown($visits(), 'device_type'),
            'operating_systems' => $this->breakdown($visits(), 'operating_system'),
            'referrers'         => $this->referrers($visits()),
        ];
    }

    public function visits(Project $project): Builder
    {
        return Visit::query()->forProject($project)->human();
    }

    /** @return list<array{date: string, clicks: int}> */
    public function daily(Builder $visits, $since): array
    {
        return $visits
            ->select(DB::raw('Date(visited_at) as day'), DB::raw('count(*) as clicks'))
            ->where('visited_at', '>=', $since)
            ->groupBy(DB::raw('Date(visited_at)'))
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['date' => (string) $row->day, 'clicks' => (int) $row->clicks])
            ->all();
    }

    /** @return list<array{name: string|null, clicks: int}> */
    public function breakdown(Builder $visits, string $column): array
    {
        return $visits
            ->groupBy($column)
            ->selectRaw($column.' as name, count(*) as clicks')
            ->orderByDesc('clicks')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'clicks' => (int) $row->clicks])
            ->all();
    }

    /** @return list<array{key: string, short_url: string, destination_url: string, title: string|null, clicks: int}> */
    public function topLinks(Project $project, $since, int $limit = 10): array
    {
        return $this->visits($project)
            ->join('short_urls', 'short_urls.id', '=', 'short_url_visits.short_url_id')
            ->where('visited_at', '>=', $since)
            ->groupBy('short_url_id', 'url_key', 'default_short_url', 'destination_url', 'title')
            ->orderByDesc('clicks')
            ->take($limit)
            ->get(['short_url_id', 'url_key', 'default_short_url', 'destination_url', 'title', DB::raw('COUNT(*) as clicks')])
            ->map(fn ($row) => [
                'key'             => $row->url_key,
                'short_url'       => $row->default_short_url,
                'destination_url' => $row->destination_url,
                'title'           => $row->title,
                'clicks'          => (int) $row->clicks,
            ])
            ->all();
    }

    /** Clicks grouped by where the link was created (panel, bot, api, miniapp). */
    public function sources(Project $project, $since): array
    {
        return $this->visits($project)
            ->join('short_urls', 'short_urls.id', '=', 'short_url_visits.short_url_id')
            ->where('visited_at', '>=', $since)
            ->groupBy('short_urls.source')
            ->orderByDesc('clicks')
            ->get(['short_urls.source as name', DB::raw('COUNT(*) as clicks')])
            ->map(fn ($row) => ['name' => $row->name, 'clicks' => (int) $row->clicks])
            ->all();
    }

    /** Referring hosts; clicks without a referer are "direct". */
    public function referrers(Builder $visits, int $limit = 10): array
    {
        return $visits
            ->groupBy('referer_url')
            ->get(['referer_url', DB::raw('COUNT(*) as clicks')])
            ->groupBy(fn ($row) => $row->referer_url
                ? (parse_url($row->referer_url, PHP_URL_HOST) ?: $row->referer_url)
                : 'direct')
            ->map(fn (Collection $rows, $host) => ['name' => $host, 'clicks' => (int) $rows->sum('clicks')])
            ->sortByDesc('clicks')
            ->take($limit)
            ->values()
            ->all();
    }
}
