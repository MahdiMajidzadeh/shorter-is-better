@extends('template.dash')

@section('title','all tokens')

@section('header','Tokens')

@section('page')
    @if(session('token'))
    <div class="bg-body d-flex align-items-center position-relative px-5 py-5 rounded-3 shadow font-semibold text-heading"
         role="alert">
        <div class="d-flex align-items-center">
            <div class="w-16 text-lg">
                <div class="icon icon-shape bg-warning text-white rounded-circle">
                    <i class="bi bi-check"></i>
                </div>
            </div>
            {{ session('token') }}
        </div>
    </div>
    @endif
    <div class="table-responsive">
        <table class="table table-hover table-nowrap table-spaced">
            <thead class="thead-light">
            <tr>
                <th scope="col">id</th>
                <th scope="col">name</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($tokens as $token)
                <tr>
                    <td>
                        {{ $token->id }}
                    </td>
                    <td data-label="Email">
                        <span>
                            {{ $token->name }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a class="btn p-0" href="#">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@section('header-actions')
    <a href="{{ url('settings/tokens/create') }}" class="btn d-inline-flex btn-sm btn-primary border-base mx-1">
        <span class=" pe-2">
            <i class="bi bi-plus-lg"></i>
        </span>
        <span>create token</span>
    </a>
@endsection