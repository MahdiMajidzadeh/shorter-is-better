@extends('template.dash')

@section('title','dashboard')

@section('header','Dashboard')

@section('page')
    @include('template.msg')

    @if(! $hasLinks)
        <flux:card class="py-16 text-center">
            <flux:icon.link class="mx-auto size-10 text-zinc-400"/>
            <flux:heading size="lg" class="mt-4">Create your first link</flux:heading>
            <flux:text class="mt-2">Shorten a URL and its click stats will show up here.</flux:text>
            <div class="mt-6">
                <flux:button href="{{ url('links/create') }}" variant="primary" icon="plus">Create link</flux:button>
            </div>
        </flux:card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <flux:card size="sm">
                <flux:text size="sm">Clicks today</flux:text>
                <flux:heading size="xl" class="mt-1 tabular-nums">{{ number_format($clicksToday) }}</flux:heading>
                @if(! is_null($todayChange))
                    <div class="mt-1 flex items-center gap-1 text-sm {{ $todayChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        <flux:icon :icon="$todayChange >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="size-4"/>
                        {{ abs($todayChange) }}% vs yesterday
                    </div>
                @else
                    <flux:text size="sm" class="mt-1">no clicks yesterday</flux:text>
                @endif
            </flux:card>

            <flux:card size="sm">
                <flux:text size="sm">Clicks, 7 days</flux:text>
                <flux:heading size="xl" class="mt-1 tabular-nums">{{ number_format($clicksWeek) }}</flux:heading>
                @if(! is_null($weekChange))
                    <div class="mt-1 flex items-center gap-1 text-sm {{ $weekChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        <flux:icon :icon="$weekChange >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" class="size-4"/>
                        {{ abs($weekChange) }}% vs prior week
                    </div>
                @else
                    <flux:text size="sm" class="mt-1">no clicks in prior week</flux:text>
                @endif
            </flux:card>

            <flux:card size="sm">
                <flux:text size="sm">Links</flux:text>
                <flux:heading size="xl" class="mt-1 tabular-nums">{{ number_format($totalLinks) }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ $newLinks }} new this week</flux:text>
            </flux:card>

            <flux:card size="sm">
                <flux:text size="sm">All-time clicks</flux:text>
                <flux:heading size="xl" class="mt-1 tabular-nums">{{ number_format($totalClicks) }}</flux:heading>
                <flux:text size="sm" class="mt-1">since {{ $firstLinkAt->format('M Y') }}</flux:text>
            </flux:card>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-5">
            <flux:card class="lg:col-span-3">
                <flux:heading size="lg">Daily clicks</flux:heading>
                <flux:text size="sm" class="mt-1">Last 30 days, bots excluded</flux:text>
                <div id="view-chart" class="mt-4"></div>
            </flux:card>

            <flux:card class="lg:col-span-2">
                <flux:heading size="lg">Top links, 7 days</flux:heading>
                <div class="mt-4 space-y-5">
                    @php $maxViews = $topLinks->max('views') ?: 1; @endphp
                    @forelse($topLinks as $link)
                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <flux:link href="{{ url('links/'.$link->url_key) }}" variant="ghost" class="truncate font-mono text-sm">
                                    /s/{{ $link->url_key }}
                                </flux:link>
                                <span class="text-sm tabular-nums text-zinc-500 dark:text-zinc-400">{{ number_format($link->views) }}</span>
                            </div>
                            <div class="mt-1.5 h-1 rounded-full bg-zinc-100 dark:bg-zinc-700">
                                <div class="h-1 rounded-full bg-blue-500" style="width: {{ round($link->views / $maxViews * 100) }}%"></div>
                            </div>
                            <flux:text class="mt-1 truncate !text-xs">{{ $link->destination_url }}</flux:text>
                        </div>
                    @empty
                        <flux:text size="sm">No clicks in the last 7 days.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <flux:card>
                <flux:heading size="lg">Referrers, 7 days</flux:heading>
                <div class="mt-4">
                    @forelse($referrers as $referrer)
                        <div class="flex items-center justify-between border-b border-zinc-100 py-2 text-sm last:border-0 dark:border-zinc-700">
                            <span class="truncate">{{ $referrer->host }}</span>
                            <span class="tabular-nums text-zinc-500 dark:text-zinc-400">{{ number_format($referrer->views) }}</span>
                        </div>
                    @empty
                        <flux:text size="sm">No clicks in the last 7 days.</flux:text>
                    @endforelse
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Recent activity</flux:heading>
                <div class="mt-4">
                    @forelse($recentVisits as $visit)
                        <div class="flex items-center gap-2 border-b border-zinc-100 py-2 text-sm last:border-0 dark:border-zinc-700">
                            <flux:icon
                                :icon="match($visit->device_type) { 'mobile' => 'device-phone-mobile', 'tablet' => 'device-tablet', default => 'computer-desktop' }"
                                class="size-4 shrink-0 text-zinc-400"
                            />
                            @if($visit->shortURL)
                                <flux:link href="{{ url('links/'.$visit->shortURL->url_key) }}" variant="ghost" class="truncate font-mono text-sm">
                                    /s/{{ $visit->shortURL->url_key }}
                                </flux:link>
                            @endif
                            <span class="ms-auto shrink-0 text-zinc-500 dark:text-zinc-400">
                                {{ \Illuminate\Support\Carbon::parse($visit->visited_at)->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <flux:text size="sm">No visits recorded yet.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @endif
@endsection

@push('js')
    @if($hasLinks)
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            var options = {
                chart: {
                    type: 'bar',
                    height: 320,
                    background: 'transparent',
                    fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif',
                    toolbar: { show: false }
                },
                theme: {
                    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 2
                    }
                },
                dataLabels: { enabled: false },
                series: [{
                    name: 'clicks',
                    data: @json($views)
                }]
            }

            var chart = new ApexCharts(document.querySelector("#view-chart"), options);
            chart.render();
        </script>
    @endif
@endpush
