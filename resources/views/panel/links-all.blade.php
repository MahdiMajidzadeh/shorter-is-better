@extends('template.dash')

@section('title','all link')

@section('header','All Link')

@section('page')
    <flux:card class="!p-4">
        <flux:table :paginate="$links">
            <flux:table.columns>
                <flux:table.column>Id</flux:table.column>
                <flux:table.column>Url</flux:table.column>
                <flux:table.column>Link</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($links as $link)
                    <flux:table.row :key="$link->id">
                        <flux:table.cell>{{ $link->id }}</flux:table.cell>
                        <flux:table.cell class="max-w-md truncate">
                            <flux:link href="{{ $link->destination_url }}" variant="subtle">
                                {{ $link->destination_url }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:link href="{{ $link->default_short_url }}">
                                {{ $link->default_short_url }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button href="{{ url('links/'. $link->url_key) }}" size="sm" icon="eye">View</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection

@section('header-actions')
    <flux:button href="{{ url('links/create') }}" variant="primary" size="sm" icon="plus">Create link</flux:button>
@endsection
