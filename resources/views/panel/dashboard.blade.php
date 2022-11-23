@extends('template.dash')

@section('title','dashboard')

@section('header','Dashboard')

@section('page')
    @if (session('msg-ok'))
        <div class="alert alert-success" role="alert">
            <div class="d-flex align-items-center">
                <div class="w-8 text-lg">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <span class="font-bold">{{ session('msg-ok') }}</span>
            </div>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            <div id="view-chart"></div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        options = {
            chart: {
                type: 'bar',
                height: 400
            },
            plotOptions: {
                bar: {
                    horizontal: false
                }
            },
            series: [{
                data: @json($views)
            }]
        }

        var chart = new ApexCharts(document.querySelector("#view-chart"), options);
        chart.render();
    </script>
@endpush

