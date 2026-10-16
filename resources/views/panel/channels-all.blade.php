@extends('template.dash')

@section('title', __('Channels'))

@section('header', __('Channels'))

@section('page')
    @include('template.msg')

    <flux:card class="!p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Username') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Chat id') }}</flux:table.column>
                <flux:table.column>{{ __('Language') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Projects') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($channels as $channel)
                    <flux:table.row :key="$channel->id">
                        <flux:table.cell class="font-medium">{{ $channel->title }}</flux:table.cell>
                        <flux:table.cell dir="ltr">{{ $channel->username }}</flux:table.cell>
                        <flux:table.cell class="hidden font-mono text-sm md:table-cell" dir="ltr">{{ $channel->chat_id }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ __('language.'.$channel->language) }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $channel->projects->pluck('name')->implode('، ') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button href="{{ url('channels/'.$channel->id.'/edit') }}" size="sm" icon="pencil-square">{{ __('Edit') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">{{ __('No channels yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection

@section('header-actions')
    <flux:button href="{{ url('channels/create') }}" variant="primary" size="sm" icon="plus">{{ __('Create channel') }}</flux:button>
@endsection
