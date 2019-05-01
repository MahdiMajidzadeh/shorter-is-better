@extends('master.base')

@section('title', 'dashboard')

@section('content')
    <div class="main-content">
        @include('master.nav')
        <div class="header bg-gradient-primary pb-7 pt-5 pt-md-8">
            <div class="container-fluid">
                <div class="header-body">
                    <div class="row">
                        <div class="col-12 col-md-9">
                            <div class="card card-stats mb-4 mb-xl-0 bg-lighter">
                                <div class="card-body">
                                    <h2 class="my-4">MAKE IT SHORT</h2>
                                    <form method="post" action="{{ url('panel/links/create') }}">
                                        @csrf
                                        <div class="form-group">
                                            <input type="text" placeholder="url" name="url" class="form-control form-control-alternative"/>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="col-12 mb-3">
                                <div class="card card-stats mb-4 mb-xl-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <h5 class="card-title text-uppercase text-muted mb-0">Total Links</h5>
                                                <span class="h2 font-weight-bold mb-0">{{ $links_all }}</span>
                                            </div>
                                            <div class="col-auto">
                                                <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                                    <i class="fas fa-link"></i>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card card-stats mb-4 mb-xl-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col">
                                                <h5 class="card-title text-uppercase text-muted mb-0">Total Views</h5>
                                                <span class="h2 font-weight-bold mb-0">{{ $views }}</span>
                                            </div>
                                            <div class="col-auto">
                                                <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                                    <i class="fas fa-eye"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page content -->
        <div class="container-fluid mt--7">
            <div class="row mt-5">
                <div class="col-xl-12 mb-5 mb-xl-0">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h3 class="mb-0">Page visits</h3>
                                </div>
                                <div class="col text-right">
                                    <a href="#!" class="btn btn-sm btn-primary">See all</a>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <!-- Projects table -->
                            <table class="table align-items-center table-flush">
                                <thead class="thead-light">
                                <tr>
                                    <th scope="col">url</th>
                                    <th scope="col">slug</th>
                                    <th scope="col">visit</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($last_links as $link)
                                    <tr>
                                        <th scope="row">
                                            {{ $link->link }}
                                        </th>
                                        <td>
                                            {{ $link->slug }}
                                        </td>
                                        <td>
                                            {{ $link->view }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @include('master.footer')
        </div>
    </div>
@endsection