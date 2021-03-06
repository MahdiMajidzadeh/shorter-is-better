@extends('master.base')

@section('title', 'Add User')

@section('content')
    <div class="main-content">
        @include('master.nav')
        <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
        <div class="container-fluid mt--7">
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="alert alert-danger fade show" role="alert">
                        <span class="alert-inner--text">{{ $error }}</span>
                    </div>
                @endforeach
            @endif
            <div class="row">
                <div class="col">
                    <form method="post" action="{{ url('panel/users/create') }}">
                        @csrf
                        <div class="card shadow">
                            <div class="card-header border-0">
                                <h3 class="mb-0">
                                    Add Shorter Mate
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <input type="text" name="name" class="form-control form-control-alternative" placeholder="name" required value="{{ old('name') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <input type="text" name="username" class="form-control form-control-alternative" placeholder="username" required value="{{ old('username') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-alternative" placeholder="password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer py-4">
                                <button type="submit" class="btn btn-success">Submit</button>
                                <a href="{{ url('panel/users') }}" class="btn btn-secondary">Dismiss</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @include('master.footer')
        </div>
    </div>
@endsection