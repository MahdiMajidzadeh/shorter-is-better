@extends('template.master')

@section('title','Hi !')

@section('content')
    <div class="bg-surface-secondary min-h-full min-h-screen d-flex align-items-center">
        <div class="container-fluid">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-8 text-center">
                    <!-- Title -->
                    <h1 class="ls-tight font-bolder display-3 mb-7">
                        {{ setting('home.title', 'Hi') }}
                        <span class="text-primary">{{ setting('home.title-accent', 'everyone') }}</span>
                    </h1>
                    <!-- Text -->
                    <p class="lead px-lg-16 mb-10">
                        {{ setting('home.subtitle') }}
                    </p>
                    <!-- Buttons -->
                    <div class="mx-n2">
                        <a href="{{ setting('home.cta-url', 'https://github.com/MahdiMajidzadeh/shorter-is-better') }}" class="btn btn-lg btn-light shadow-sm mx-2 px-lg-8">
                            {{ setting('home.cta-title', 'See Github Ripo') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
