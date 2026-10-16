<?php

namespace App\Services;

use App\Models\Link;
use App\Models\Visit;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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

        // One pass over the project's visits for every headline number
        // instead of five separate counts of the same rows.
        $clicks = $this->visits($project)->selectRaw(
            'count(*) as total, '
            .'sum(case when visited_at >= ? then 1 else 0 end) as today, '
            .'sum(case when visited_at between ? and ? then 1 else 0 end) as yesterday, '
            .'sum(case when visited_at >= ? then 1 else 0 end) as period, '
            .'sum(case when visited_at between ? and ? then 1 else 0 end) as previous',
            [today(), today()->subDay(), today(), $since, $previous, $since]
        )->first();

        $links = Link::forProject($project)->selectRaw(
            'count(*) as total, sum(case when created_at >= ? then 1 else 0 end) as recent',
            [$since]
        )->first();

        return [
            'days'             => $days,
            'since'            => $since->toIso8601String(),
            'clicks_today'     => (int) $clicks->today,
            'clicks_yesterday' => (int) $clicks->yesterday,
            'clicks'           => (int) $clicks->period,
            'clicks_previous'  => (int) $clicks->previous,
            'clicks_total'     => (int) $clicks->total,
            'links_total'      => (int) $links->total,
            'links_new'        => (int) $links->recent,
            'daily'            => $this->daily($this->visits($project), $since),
            'top_links'        => $this->topLinks($project, $since),
            'devices'          => $this->breakdown($this->visits($project)->where('visited_at', '>=', $since), 'device_type'),
            'sources'          => $this->sources($project, $since),
            'channels'         => $this->channels($project, $since),
            'referrers'        => $this->referrers($this->visits($project)->where('visited_at', '>=', $since)),
        ];
    }

    public function linkStats(Link $link, int $days = 30): array
    {
        $since = now()->subDays($days);
        $visits = fn () => Visit::query()->where('short_url_id', $link->id)->human();

        $clicks = $visits()->selectRaw(
            'count(*) as total, sum(case when visited_at >= ? then 1 else 0 end) as period',
            [$since]
        )->first();

        return [
            'days'              => $days,
            'clicks_total'      => (int) $clicks->total,
            'clicks'            => (int) $clicks->period,
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
        // Rank by link id first and fetch the links after: grouping the
        // joined rows by destination_url (a TEXT column) forced MariaDB
        // through an on-disk temporary table.
        $ranked = $this->visits($project)
            ->where('visited_at', '>=', $since)
            ->groupBy('short_url_id')
            ->orderByDesc('clicks')
            ->orderBy('short_url_id')
            ->take($limit)
            ->get(['short_url_id', DB::raw('COUNT(*) as clicks')]);

        $links = Link::whereIn('id', $ranked->pluck('short_url_id'))->get()->keyBy('id');

        return $ranked
            ->filter(fn ($row) => $links->has($row->short_url_id))
            ->map(fn ($row) => [
                'key'             => $links[$row->short_url_id]->url_key,
                'short_url'       => $links[$row->short_url_id]->default_short_url,
                'destination_url' => $links[$row->short_url_id]->destination_url,
                'title'           => $links[$row->short_url_id]->title,
                'clicks'          => (int) $row->clicks,
            ])
            ->values()
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

    /** Clicks grouped by the channel the link was posted to; links never posted are "none". */
    public function channels(Project $project, $since): array
    {
        return $this->visits($project)
            ->join('short_urls', 'short_urls.id', '=', 'short_url_visits.short_url_id')
            ->leftJoin('channels', 'channels.id', '=', 'short_urls.channel_id')
            ->where('visited_at', '>=', $since)
            ->groupBy('short_urls.channel_id', 'channels.title')
            ->orderByDesc('clicks')
            ->get(['short_urls.channel_id', 'channels.title', DB::raw('COUNT(*) as clicks')])
            ->map(fn ($row) => ['id' => $row->channel_id, 'name' => $row->title, 'clicks' => (int) $row->clicks])
            ->all();
    }

    /**
     * The latest human clicks with their link, for the activity feed. Ordered
     * by id, which grows with visited_at, so the primary key serves the sort
     * instead of a filesort over the project's whole history.
     */
    public function recent(Project $project, int $limit = 8): EloquentCollection
    {
        return $this->visits($project)->with('link')->orderByDesc('id')->take($limit)->get();
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
