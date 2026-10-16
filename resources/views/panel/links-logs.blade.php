@extends('template.dash')

@section('title', __('Visit log'))

@section('header', __('Visit log'))

@section('page')
    <flux:card class="!p-4">
        <flux:table :paginate="$logs">
            <flux:table.columns>
                <flux:table.column>{{ __('Link') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Operating system') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Browser') }}</flux:table.column>
                <flux:table.column>{{ __('Device') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('IP') }}</flux:table.column>
                <flux:table.column>{{ __('Visited at') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($logs as $log)
                    <flux:table.row :key="$log->id">
                        <flux:table.cell class="max-w-56 truncate">
                            @if($log->link)
                                <flux:link href="{{ url('links/'. $log->link->url_key) }}" class="font-mono text-sm" dir="ltr">/s/{{ $log->link->url_key }}</flux:link>
                                <div class="truncate text-xs text-zinc-500">{{ $log->link->displayTitle() }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ trim($log->operating_system.' '.$log->operating_system_version) }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ trim($log->browser.' '.$log->browser_version) }}</flux:table.cell>
                        <flux:table.cell>
                            @if($log->device_type)
                                <flux:badge size="sm">{{ __('device.'.$log->device_type) }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell" dir="ltr">{{ $log->ip_address }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ local_date($log->visited_at, true) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection
