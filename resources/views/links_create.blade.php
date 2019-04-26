@extends('master.base')

@section('title', 'all links')

@section('nav-title', 'All Links')

@section('content')
    <div class="main-content">
        @include('master.nav')
        <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
        <div class="container-fluid mt--7">
            <div class="row">
                <div class="col">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h3 class="mb-0">
                                Your Valid Link

                            </h3>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-alternative" placeholder="link ...">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-alternative" placeholder="slug (optional)">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @include('master.footer')
        </div>
    </div>
@endsection