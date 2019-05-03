@extends('master.base')

@section('title', 'Setting')

@section('content')
    <div class="main-content">
        @include('master.nav')
        <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
        <div class="container-fluid mt--7">
            @if(session()->has('msg-ok'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="alert-inner--text"><strong>Success!</strong></span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <div class="row">
                <div class="col">
                    <form method="post" action="{{ url('panel/setting') }}">
                        @csrf
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3 class="mb-0">
                                Setting
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label>Default Domain</label>
                                        <input type="text" class="form-control" name="domain" value="{{ $domain }}">
                                        <small class="form-text text-muted"></small>
                                    </div>
                                    <div class="form-group">
                                        <label>Default Url</label>
                                        <input type="text" class="form-control" name="defaultURL" value="{{ $defaultURL }}">
                                        <small class="form-text text-muted"></small>
                                    </div>
                                    <div class="form-group">
                                        <label>Slug length</label>
                                        <input type="text" class="form-control" name="slugLength" value="{{ $slugLength }}">
                                        <small class="form-text text-muted"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer py-4">
                            <button type="submit" class="btn btn-success">Submit</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
            @include('master.footer')
        </div>
    </div>
@endsection