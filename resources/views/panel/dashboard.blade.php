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
