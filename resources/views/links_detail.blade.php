@extends('master.base')

@section('title', 'Create new Shorter')

@section('content')
    <div class="main-content">
        @include('master.nav')
        <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
        <div class="container-fluid mt--7">
            <div class="row">
                <div class="col-12 col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase text-muted ls-1 mb-1">Visitors</h6>
                                    <h2 class="mb-0">System</h2>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="chart-system" class="chart-canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase text-muted ls-1 mb-1">Visitors</h6>
                                    <h2 class="mb-0">Browser</h2>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="chart-browser" class="chart-canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase text-muted ls-1 mb-1">Visitors</h6>
                                    <h2 class="mb-0">Platform</h2>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="chart-platform" class="chart-canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase text-muted ls-1 mb-1">Visitors</h6>
                                    <h2 class="mb-0">Device</h2>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="chart-device" class="chart-canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @include('master.footer')
        </div>
    </div>
@endsection

@push('js')
    <script>
        var colors = [
            'rgb(1, 87, 155)',
            'rgb(2, 119, 189)',
            'rgb(2, 136, 209)',
            'rgb(3, 155, 229)',
            'rgb(3, 169, 244)',
            'rgb(41, 182, 246)',
            'rgb(79, 195, 247)'
        ];

        var config_system = {
            type: 'pie',
            data: {
                datasets: [{
                    data: @json($system->values()->all()),
                    backgroundColor: colors,
                }],
                labels: @json($system->keys()->all())
            }
        };

        var config_browser = {
            type: 'pie',
            data: {
                datasets: [{
                    data: @json($browser->values()->all()),
                    backgroundColor: colors,
                }],
                labels: @json($browser->keys()->all())
            }
        };

        var config_platform = {
            type: 'pie',
            data: {
                datasets: [{
                    data: @json($platform->values()->all()),
                    backgroundColor: colors,
                }],
                labels: @json($platform->keys()->all())
            }
        };

        var config_device = {
            type: 'pie',
            data: {
                datasets: [{
                    data: @json($device->values()->all()),
                    backgroundColor: colors,
                }],
                labels: @json($device->keys()->all())
            }
        };

        window.onload = function() {
            var ctx_system = document.getElementById('chart-system').getContext('2d');
            window.myPie = new Chart(ctx_system, config_system);

            var ctx_browser = document.getElementById('chart-browser').getContext('2d');
            window.myPie = new Chart(ctx_browser, config_browser);

            var ctx_platform = document.getElementById('chart-platform').getContext('2d');
            window.myPie = new Chart(ctx_platform, config_platform);

            var ctx_device = document.getElementById('chart-device').getContext('2d');
            window.myPie = new Chart(ctx_device, config_device);
        };
    </script>
@endpush