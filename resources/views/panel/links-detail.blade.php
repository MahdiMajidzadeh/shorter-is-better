@extends('template.dash')

@section('title','detail link')

@section('header','Link')

@section('page')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <div class="icon icon-shape text-lg bg-primary text-white rounded-circle">
                                <i class="bi bi-link-45deg"></i>
                            </div>
                        </div>
                        <div class="ps-4">
                            <span class="d-block text-sm text-muted font-semibold mb-1">
                                Original Link
                                <button href="#" class="btn btn-square btn-neutral btn-sm copy" data-clipboard-text="{{ $short->destination_url }}">
                                  <span class="svg-icon">
                                    <i class="bi bi-clipboard"></i>
                                  </span>
                                </button>
                            </span>
                            <div class="d-flex align-items-center h4 mb-0 text-break">
                                <a href="{{ $short->destination_url }}">
                                    {{ $short->destination_url }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <div class="icon icon-shape text-lg bg-primary text-white rounded-circle">
                                <i class="bi bi-hash"></i>
                            </div>
                        </div>
                        <div class="ps-4">
                            <span class="d-block text-sm text-muted font-semibold mb-1">
                                Shorten Link
                                <button href="#" class="btn btn-square btn-neutral btn-sm copy" data-clipboard-text="{{ $short->default_short_url }}">
                                  <span class="svg-icon">
                                    <i class="bi bi-clipboard"></i>
                                  </span>
                                </button>
                            </span>
                            <div class="d-flex align-items-center h4 mb-0 user-select-all">
                                <a href="{{ $short->default_short_url }}">
                                    {{ $short->default_short_url }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr class="my-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <div class="icon icon-shape text-lg bg-primary text-light rounded-circle">
                                <i class="bi bi-bar-chart"></i>
                            </div>
                        </div>
                        <div class="ps-4">
                            <span class="d-block text-sm text-muted font-semibold mb-1">Daily Visit</span>
                            <div class="d-flex align-items-center h4 mb-0">
                                {{ $short->visits()->count() }}
                            </div>
                        </div>
                    </div>
                    <div id="view-chart"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Operating System</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($operating_system as $item)
                            <div class="list-group-item d-flex align-items-center px-0 py-2 border-0">
                                <div>
                                    <span
                                            class="d-block h6 font-regular mb-0 stretched-link">{{ $item['name']?: "[other]" }}</span>
                                </div>
                                <div class="ms-auto text-end">
                                    <span class="badge bg-tint-secondary rounded-pill  text-muted">{{ $item['total'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>browser</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($browser as $item)
                            <div class="list-group-item d-flex align-items-center px-0 py-2 border-0">
                                <div>
                                    <span
                                            class="d-block h6 font-regular mb-0 stretched-link">{{ $item['name']?: "[other]" }}</span>
                                </div>
                                <div class="ms-auto text-end">
                                    <span class="badge bg-tint-secondary rounded-pill  text-muted">{{ $item['total'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Device Type</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($device_type as $item)
                            <div class="list-group-item d-flex align-items-center px-0 py-2 border-0">
                                <div>
                                    <span
                                            class="d-block h6 font-regular mb-0 stretched-link">{{ $item['name']?: "[other]" }}</span>
                                </div>
                                <div class="ms-auto text-end">
                                    <span class="badge bg-tint-secondary rounded-pill  text-muted">{{ $item['total'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="exampleModal" tabindex="-1" aria-labelledby="modal_example" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-4">
                <div class="modal-body">
                    <div class="text-center py-5 px-5">
                        <!-- Icon -->
                        <div class="icon icon-shape rounded-circle bg-soft-danger text-danger text-lg">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                        </div>
                        <!-- Title -->
                        <h3 class="mt-7 mb-4">Delete Short Link?</h3>
                        <!-- Text -->
                        <p class="text-sm text-muted">
                            We will delete Short Link and all <strong> {{ $short->visits()->count() }} Views</strong>.
                            This action can not be undone. Are you sure you want to do this?
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-neutral" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ url('links/delete/'. $short->id) }}" class="btn btn-sm btn-danger">Delete MF</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('header-actions')
    <button class="btn btn-danger  btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-trash"></i>
        Delete
    </button>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.10/dist/clipboard.min.js"></script>
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

        var clipboard = new ClipboardJS('.copy');
        clipboard.on('success', function(e) {
            var tooltip = new bootstrap.Tooltip( e.trigger, {
                title: "copied !",
                trigger : "manual"
            });

            tooltip.show();
            setTimeout(() => {
                tooltip.hide();
            }, 2000);

            e.clearSelection();
        });
    </script>
@endpush
