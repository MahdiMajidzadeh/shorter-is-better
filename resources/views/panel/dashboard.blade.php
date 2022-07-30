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
        <div class="card-body h-3" >
            <canvas id="view-chart"  width="400" height="400"></canvas>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.8.2/chart.min.js"></script>
    <script>
        const ctx = document.getElementById('view-chart');
        const myChart = new Chart(ctx, {
            type: 'line',
            data: @json($views),
            options: {
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            },
        });
    </script>
@endpush

