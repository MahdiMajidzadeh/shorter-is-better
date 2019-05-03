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
                                Your Shorter Mates
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-center table-flush">
                                <thead class="thead-light">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">UserName</th>
                                    {{--<th scope="col"></th>--}}
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <th scope="row">
                                            {{ $user->name }}
                                        </th>
                                        <td>
                                            {{ $user->username }}
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