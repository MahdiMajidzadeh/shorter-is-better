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
                                <a  href="{{ url('panel/links/create') }}" class="btn btn-default right--5">Create</a>
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush">
                                <thead class="thead-light">
                                <tr>
                                    <th scope="col">Shorter</th>
                                    <th scope="col">Link</th>
                                    <th scope="col">Views</th>
                                    <th scope="col">Creator</th>
                                    <th scope="col"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <th scope="row">
                                        <a href="#">mjdz.ir/sddf</a>
                                    </th>
                                    <td>
                                        <a href="#">laravel.com/docs/5.8/routing#route-group-namespaces</a>
                                    </td>
                                    <td>
                                        456
                                    </td>
                                    <td>
                                        Mahdi Majidzadeh
                                    </td>
                                    <td class="text-right">
                                        <a href="#" class="btn btn-icon btn-secondary btn-sm">
                                            <span class="btn-inner--icon"><i class="fa fa-chart-bar"></i></span>
                                        </a>
                                        <div class="dropdown">
                                            <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <a href="#">mjdz.ir/sddf</a>
                                    </th>
                                    <td>
                                        <a href="#">laravel.com/docs/5.8/routing#route-group-namespaces</a>
                                    </td>
                                    <td>
                                        456
                                    </td>
                                    <td>
                                        Mahdi Majidzadeh
                                    </td>
                                    <td class="text-right">
                                        <a href="#" class="btn btn-icon btn-secondary btn-sm">
                                            <span class="btn-inner--icon"><i class="fa fa-chart-bar"></i></span>
                                        </a>
                                        <div class="dropdown">
                                            <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <a href="#">mjdz.ir/sddf</a>
                                    </th>
                                    <td>
                                        <a href="#">laravel.com/docs/5.8/routing#route-group-namespaces</a>
                                    </td>
                                    <td>
                                        456
                                    </td>
                                    <td>
                                        Mahdi Majidzadeh
                                    </td>
                                    <td class="text-right">
                                        <a href="#" class="btn btn-icon btn-secondary btn-sm">
                                            <span class="btn-inner--icon"><i class="fa fa-chart-bar"></i></span>
                                        </a>
                                        <div class="dropdown">
                                            <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <a href="#">mjdz.ir/sddf</a>
                                    </th>
                                    <td>
                                        <a href="#">laravel.com/docs/5.8/routing#route-group-namespaces</a>
                                    </td>
                                    <td>
                                        456
                                    </td>
                                    <td>
                                        Mahdi Majidzadeh
                                    </td>
                                    <td class="text-right">
                                        <a href="#" class="btn btn-icon btn-secondary btn-sm">
                                            <span class="btn-inner--icon"><i class="fa fa-chart-bar"></i></span>
                                        </a>
                                        <div class="dropdown">
                                            <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer py-4">
                            <nav aria-label="...">
                                <ul class="pagination justify-content-end mb-0">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1">
                                            <i class="fas fa-angle-left"></i>
                                            <span class="sr-only">Previous</span>
                                        </a>
                                    </li>
                                    <li class="page-item active">
                                        <a class="page-link" href="#">1</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">2 <span class="sr-only">(current)</span></a>
                                    </li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">
                                            <i class="fas fa-angle-right"></i>
                                            <span class="sr-only">Next</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            @include('master.footer')
        </div>
    </div>
@endsection