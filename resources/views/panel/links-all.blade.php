@extends('template.dash')

@section('title','all link')

@section('header','All Link')

@section('page')
    <div class="table-responsive">
        <table class="table table-hover table-nowrap table-spaced">
            <thead class="thead-light">
            <tr>
                <th scope="col">id</th>
                <th scope="col">url</th>
                <th scope="col">link</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($links as $link)
                <tr>
                    <td>
                        {{ $link->id }}
                    </td>
                    <td data-label="Email">
                        <a class="text-current" href="{{ $link->destination_url }}">
                            {{ $link->destination_url }}
                        </a>
                    </td>
                    <td data-label="Phone">
                        <a class="text-current" href="{{ $link->default_short_url }}">
                            {{ $link->default_short_url }}
                        </a>
                    </td>
                    <td class="text-end">
                        <a class="btn p-0" href="{{ url('links/'. $link->url_key)}}">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $links->links() }}
@endsection
