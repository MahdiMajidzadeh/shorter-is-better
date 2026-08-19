@extends('template.dash')

@section('title','dashboard')

@section('header','Dashboard')

@section('page')
    @include('template.msg')

    <flux:card>
        <flux:heading size="lg">Daily Visits</flux:heading>
        <flux:text class="mt-1">Non-bot views over the last 30 days</flux:text>
        <div id="view-chart" class="mt-6"></div>
    </flux:card>
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
