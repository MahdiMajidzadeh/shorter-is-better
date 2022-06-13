@extends('template.dash')

@section('title','all bots')

@section('header','Bots')

@section('page')
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
            @foreach($bots as $bot)
                <tr>
                    <td>
                        {{ $bot->id }}
                    </td>
                    <td data-label="Email">
                        <span>
                            {{ $bot->name }}
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
    <a href="{{ url('settings/bots/create') }}" class="btn d-inline-flex btn-sm btn-primary border-base mx-1">
        <span class=" pe-2">
            <i class="bi bi-plus-lg"></i>
        </span>
        <span>create bot</span>
    </a>
@endsection