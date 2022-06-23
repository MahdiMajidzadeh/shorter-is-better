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
    <hr class="my-5"
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
    </div>

@endsection
