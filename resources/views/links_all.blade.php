@extends('master.base')

@section('title', 'All Links')

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
                                @foreach($links as $link)
                                <tr>
                                    <th scope="row">
                                        <a href="{{ $setting['domain']. '/'. $link->slug }}">
                                            {{ $setting['domain']. '/'. $link->slug }}
                                        </a>
                                    </th>
                                    <td>
                                        <a href="{{ $link->url }}">{{ $link->link }}</a>
                                    </td>
                                    <td>
                                        {{ $link->view }}
                                    </td>
                                    <td>
                                        {{ $link->user->name }}
                                    </td>
                                    <td class="text-right">
                                        <a href="#" class="btn btn-icon btn-secondary btn-sm">
                                            <span class="btn-inner--icon"><i class="fa fa-chart-bar"></i></span>
                                        </a>
                                        {{--<div class="dropdown">--}}
                                            {{--<a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">--}}
                                                {{--<i class="fas fa-ellipsis-v"></i>--}}
                                            {{--</a>--}}
                                            {{--<div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">--}}
                                                {{--<a class="dropdown-item" href="#">Action</a>--}}
                                                {{--<a class="dropdown-item" href="#">Another action</a>--}}
                                                {{--<a class="dropdown-item" href="#">Something else here</a>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                    </td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer py-4">
                            <nav aria-label="...">
                                <ul class="pagination justify-content-end mb-0">
                                    {{ $links->links() }}
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