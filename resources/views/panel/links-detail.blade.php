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
                            <span class="d-block text-sm text-muted font-semibold mb-1">link</span>
                            <div class="d-flex align-items-center h4 mb-0 text-break">
                                {{ $short->destination_url }}
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
                            <span class="d-block text-sm text-muted font-semibold mb-1">link</span>
                            <div class="d-flex align-items-center h4 mb-0 user-select-all">
                                {{ $short->default_short_url }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr class="my-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <div class="icon icon-shape text-lg bg-primary text-white rounded-circle">
                                <i class="bi bi-eye"></i>
                            </div>
                        </div>
                        <div class="ps-4">
                            <span class="d-block text-sm text-muted font-semibold mb-1">Total visit</span>
                            <div class="d-flex align-items-center h4 mb-0">
                                {{ $short->visits()->count() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="ps-4">
                            <div class="d-flex align-items-center h4 mb-0">
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                    <i class="bi bi-trash"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
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
