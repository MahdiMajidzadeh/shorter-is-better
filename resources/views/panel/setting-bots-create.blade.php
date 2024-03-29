@extends('template.dash')

@section('title','create bots')

@section('header','Create Bots')

@section('page')
    <div class="">
        <div class="container py-7">
            <div class="row">
                @include('template.msg')
                <form method="post" action="{{ url('settings/bots/create') }}">
                    @csrf
                    <div class="col-lg-10 mx-auto">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Name</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="">
                                    <input type="text" class="form-control" name="name">
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center my-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Token</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="">
                                    <input type="text" class="form-control" name="token">
                                </div>
                            </div>
                        </div>
                        <hr class="my-6"/>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
