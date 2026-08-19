@extends('template.dash')

@section('title','detail link')

@section('header','Link')

@section('page')
    <div class="grid gap-6 md:grid-cols-2">
        <flux:card>
            <flux:input label="Original Link" icon="link" value="{{ $short->destination_url }}" readonly copyable/>
            <flux:link href="{{ $short->destination_url }}" variant="subtle" class="mt-2 inline-block text-sm">Open link</flux:link>
        </flux:card>
        <flux:card>
            <flux:input label="Shorten Link" icon="hashtag" value="{{ $short->default_short_url }}" readonly copyable/>
            <flux:link href="{{ $short->default_short_url }}" variant="subtle" class="mt-2 inline-block text-sm">Open link</flux:link>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <flux:text>Total Visits</flux:text>
        <flux:heading size="xl" class="mt-1">{{ $short->visits()->count() }}</flux:heading>
        <div id="view-chart" class="mt-6"></div>
    </flux:card>

    <div class="mt-6 grid gap-6 md:grid-cols-3">
        <flux:card>
            <flux:heading>Operating System</flux:heading>
            <div class="mt-4 space-y-3">
                @foreach($operating_system as $item)
                    <div class="flex items-center justify-between">
                        <flux:text>{{ $item['name'] ?: '[other]' }}</flux:text>
                        <flux:badge size="sm">{{ $item['total'] }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </flux:card>
        <flux:card>
            <flux:heading>Browser</flux:heading>
            <div class="mt-4 space-y-3">
                @foreach($browser as $item)
                    <div class="flex items-center justify-between">
                        <flux:text>{{ $item['name'] ?: '[other]' }}</flux:text>
                        <flux:badge size="sm">{{ $item['total'] }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </flux:card>
        <flux:card>
            <flux:heading>Device Type</flux:heading>
            <div class="mt-4 space-y-3">
                @foreach($device_type as $item)
                    <div class="flex items-center justify-between">
                        <flux:text>{{ $item['name'] ?: '[other]' }}</flux:text>
                        <flux:badge size="sm">{{ $item['total'] }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>

    <flux:modal name="delete-link" class="min-w-88">
        <div class="space-y-6 text-center">
            <div>
                <flux:icon.exclamation-triangle class="mx-auto size-10 text-red-500"/>
                <flux:heading size="lg" class="mt-4">Delete Short Link?</flux:heading>
                <flux:text class="mt-2">
                    We will delete this short link and all <strong>{{ $short->visits()->count() }} views</strong>.
                    This action can not be undone. Are you sure you want to do this?
                </flux:text>
            </div>
            <div class="flex justify-center gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm">Cancel</flux:button>
                </flux:modal.close>
                <form action="{{ url('links/delete/'. $short->id) }}" method="post">
                    @csrf
                    <flux:button type="submit" variant="danger" size="sm">Delete MF</flux:button>
                </form>
            </div>
        </div>
    </flux:modal>
@endsection

@section('header-actions')
    <flux:modal.trigger name="delete-link">
        <flux:button variant="danger" size="sm" icon="trash">Delete</flux:button>
    </flux:modal.trigger>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        var options = {
            chart: {
                type: 'bar',
                height: 400,
                background: 'transparent',
                fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif'
            },
            theme: {
                mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            },
            plotOptions: {
                bar: {
                    horizontal: false
                }
            },
            series: [{
                name: 'views',
                data: @json($views)
            }]
        }

        var chart = new ApexCharts(document.querySelector("#view-chart"), options);
        chart.render();
    </script>
@endpush
