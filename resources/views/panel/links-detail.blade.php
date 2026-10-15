@extends('template.dash')

@section('title', __('Link'))

@section('header', $short->displayTitle())

@section('page')
    @include('template.msg')

    <div class="grid gap-6 md:grid-cols-2">
        <flux:card>
            <flux:input label="{{ __('Original link') }}" icon="link" value="{{ $short->destination_url }}" readonly copyable dir="ltr"/>
            <flux:link href="{{ $short->destination_url }}" variant="subtle" class="mt-2 inline-block text-sm">{{ __('Open link') }}</flux:link>
        </flux:card>
        <flux:card>
            <flux:input label="{{ __('Short link') }}" icon="hashtag" value="{{ $short->default_short_url }}" readonly copyable dir="ltr"/>
            <div class="mt-2 flex flex-wrap gap-x-4 text-sm">
                <flux:link href="{{ $short->default_short_url }}" variant="subtle">{{ __('Open link') }}</flux:link>
                <flux:text size="sm">{{ __('Created :date from :source', ['date' => local_date($short->created_at, true), 'source' => __('source.'.$short->source)]) }}@if($short->channel) · {{ __('Channel') }}: {{ $short->channel->title }}@endif</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <flux:text>{{ __('Clicks, bots excluded') }}</flux:text>
        <flux:heading size="xl" class="mt-1">{{ local_number($stats['clicks_total']) }}</flux:heading>
        <flux:text size="sm" class="mt-1">{{ __(':count in the last 30 days', ['count' => local_number($stats['clicks'])]) }}</flux:text>
        <div id="view-chart" class="mt-6" dir="ltr"></div>
    </flux:card>

    <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach([['title' => __('Operating system'), 'rows' => $stats['operating_systems']], ['title' => __('Browser'), 'rows' => $stats['browsers']], ['title' => __('Device'), 'rows' => $stats['devices'], 'device' => true], ['title' => __('Referrers'), 'rows' => $stats['referrers'], 'referrer' => true]] as $block)
            <flux:card>
                <flux:heading>{{ $block['title'] }}</flux:heading>
                <div class="mt-4 space-y-3">
                    @forelse($block['rows'] as $item)
                        <div class="flex items-center justify-between">
                            <flux:text class="truncate" dir="auto">{{ $item['name'] ? (($block['device'] ?? false) ? __('device.'.$item['name']) : (($block['referrer'] ?? false) && $item['name'] === 'direct' ? __('Direct') : $item['name'])) : __('Unknown') }}</flux:text>
                            <flux:badge size="sm">{{ local_number($item['clicks']) }}</flux:badge>
                        </div>
                    @empty
                        <flux:text size="sm">{{ __('No clicks yet.') }}</flux:text>
                    @endforelse
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="delete-link" class="min-w-88">
        <div class="space-y-6 text-center">
            <div>
                <flux:icon.exclamation-triangle class="mx-auto size-10 text-red-500"/>
                <flux:heading size="lg" class="mt-4">{{ __('Delete this short link?') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('The link and its :count recorded visits will be removed. This cannot be undone.', ['count' => local_number($short->visits()->count())]) }}
                </flux:text>
            </div>
            <div class="flex justify-center gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <form action="{{ url('links/delete/'. $short->id) }}" method="post">
                    @csrf
                    <flux:button type="submit" variant="danger" size="sm">{{ __('Delete') }}</flux:button>
                </form>
            </div>
        </div>
    </flux:modal>
@endsection

@section('header-actions')
    <flux:modal.trigger name="delete-link">
        <flux:button variant="danger" size="sm" icon="trash">{{ __('Delete') }}</flux:button>
    </flux:modal.trigger>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        var chart = new ApexCharts(document.querySelector("#view-chart"), {
            chart: { type: 'bar', height: 400, background: 'transparent', fontFamily: 'Vazirmatn, Inter, ui-sans-serif, system-ui, sans-serif', toolbar: { show: false } },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
            plotOptions: { bar: { horizontal: false, borderRadius: 2 } },
            dataLabels: { enabled: false },
            series: [{ name: @json(__('Clicks')), data: @json(collect($stats['daily'])->map(fn ($d) => ['x' => local_date($d['date']), 'y' => $d['clicks']])->values()) }]
        });
        chart.render();
    </script>
@endpush
