@extends('template.master')

@section('title','login')

@section('content')
    <div class="px-5 py-5 p-lg-0">
        <div class="d-flex justify-content-center">
            <div class="col-12 col-md-9 col-lg-7 min-h-lg-screen d-flex flex-column justify-content-center position-relative">
                <div class="row">
                    <div class="col-xl-5 col-lg-10 col-md-9 mx-auto">
                        <div class="text-center">
                            <a class="navbar-brand" href="#">
                                <img src="{{ asset('favicon.png') }}" class="w-12">
                            </a>
                            <div class="py-7">
                                <h1 class="ls-tight font-bolder h2">
                                    Sign in to your account
                                </h1>
                                <p class="">
                                    Let's build some shorts
                                </p>
                            </div>
                        </div>
                        <form action="{{ url('auth') }}" method="post">
                            @csrf
                            <div class="form-group-stacked mb-5">
                                <div>
                                    <div class="">
                                        <label class="form-label visually-hidden" for="username">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                    </div>
                                </div>
                                <div>
                                    <div class="">
                                        <label class="form-label visually-hidden" for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="current-password">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary w-full">
                                    Sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
