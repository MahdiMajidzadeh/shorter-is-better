@extends('template.master')

@section('title','Hi !')

@section('content')
    <div class="px-5 py-5 p-lg-0">
        <div class="d-flex justify-content-center">
            <div class="col-12 col-md-9 col-lg-7 min-h-screen d-flex flex-column justify-content-center position-relative">
                <div class="row">
                    <div class="col-xl-5 col-lg-10 col-md-9 mx-auto">
                        <div class="text-center">
                            <a class="navbar-brand" href="https://mahdi.majidzadeh.ir">
                                <img src="{{ asset('favicon.png') }}" class="w-12">
                            </a>
                            <div class="py-7">
                                <h1 class="ls-tight font-bolder h2">
                                    Hi !
                                </h1>
                                <p class="my-3">
                                    My name is Mahdi and you are in wrong place
                                </p>
                                <p class="">
                                    <a href="https://mahdi.majidzadeh.ir" class="btn btn-block btn-primary">Visit My
                                        Blog</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
