@extends('template.dash')

@section('title','all link')

@section('header','All Link')

@section('page')
    <div class="table-responsive">
        <table class="table table-hover table-nowrap">
            <thead class="table-light">
            <tr>
                <th scope="col" width="5%">id</th>
                <th scope="col" width="40%">url</th>
                <th scope="col" width="15%">link</th>
                <th scope="col" width="35%">title</th>
                <th width="5%"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($links as $link)
            <tr>
                <td>
                    {{ $link->id }}
                </td>
                <td>
                    <a class="text-current text-wrap" href="{{ $link->destination_url }}">
                        {{ $link->destination_url }}
                    </a>
                </td>
                <td>
                    <a class="text-current" href="{{ $link->default_short_url }}">
                        {{ $link->default_short_url }}
                    </a>
                </td>
                <td>

                </td>
                <td class="text-end">
                    <a href="{{ url('links/'. $link->url_key)}}" class="btn btn-sm btn-neutral">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="my-2">
        {{ $links->links() }}
    </div>
@endsection

@section('header-actions')
    <a href="{{ url('links/create') }}" class="btn d-inline-flex btn-sm btn-primary border-base mx-1">
        <span class=" pe-2">
            <i class="bi bi-plus-lg"></i>
        </span>
        <span>create link</span>
    </a>
@endsection