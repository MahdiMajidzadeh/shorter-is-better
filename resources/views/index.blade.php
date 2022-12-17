@extends('template.master')

@section('title','Hi !')

@section('content')
    <div class="bg-surface-secondary min-h-full min-h-screen d-flex align-items-center">
        <div class="container-fluid">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-8 text-center">
                    <!-- Title -->
                    <h1 class="ls-tight font-bolder display-3 mb-7">
                        Hi <span class="text-primary">everyone</span>
                    </h1>
                    <!-- Text -->
                    <p class="lead px-lg-16 mb-10">
                        Plan, build and launch beautiful and consistent user interfaces for the web that drives
                        meaningful engagement and growth for your brand.
                    </p>
                    <!-- Buttons -->
                    <div class="mx-n2">
                        <a href="#" class="btn btn-lg btn-light shadow-sm mx-2 px-lg-8">
                            Learn more
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
