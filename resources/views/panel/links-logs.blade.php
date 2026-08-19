@extends('template.dash')

@section('title','Visit Log')

@section('header','Visit Log')

@section('page')
    <flux:card class="!p-4">
        <flux:table :paginate="$logs">
            <flux:table.columns>
                <flux:table.column>Id</flux:table.column>
                <flux:table.column>Url</flux:table.column>
                <flux:table.column>Short</flux:table.column>
                <flux:table.column>OS</flux:table.column>
                <flux:table.column>Browser</flux:table.column>
                <flux:table.column>Device</flux:table.column>
                <flux:table.column>Ip</flux:table.column>
                <flux:table.column>Visited At</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($logs as $log)
                    <flux:table.row :key="$log->id">
                        <flux:table.cell>{{ $log->id }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate">
                            <flux:link href="{{ $log->shortURL->destination_url }}" variant="subtle">
                                {{ $log->shortURL->destination_url }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:link href="{{ url('links/'. $log->shortURL->url_key) }}">
                                {{ $log->shortURL->default_short_url }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ trim($log->operating_system.' '.$log->operating_system_version) }}</flux:table.cell>
                        <flux:table.cell>{{ trim($log->browser.' '.$log->browser_version) }}</flux:table.cell>
                        <flux:table.cell>
                            @if($log->device_type)
                                <flux:badge size="sm">{{ $log->device_type }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $log->ip_address }}</flux:table.cell>
                        <flux:table.cell>{{ $log->visited_at }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection
