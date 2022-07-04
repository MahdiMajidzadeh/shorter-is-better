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
@endsection

@push('js')
    <script scr="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
    <script>
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: @json($views),
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Chart.js Bar Chart'
                    }
                }
            },
        });
    </script>
@endpush

