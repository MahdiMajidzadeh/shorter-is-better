@extends('template.dash')

@section('title', __('Users'))

@section('header', __('Users'))

@section('page')
    @include('template.msg')

    <flux:card class="!p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Username') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Role') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Projects') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell class="font-medium">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell dir="ltr">{{ $user->username }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $user->is_admin ? __('Admin') : __('User') }}</flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">{{ $user->is_admin ? __('All projects') : $user->projects->pluck('name')->implode('، ') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$user->is_active ? 'green' : 'zinc'">{{ $user->is_active ? __('Active') : __('Disabled') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button href="{{ url('users/'.$user->id.'/edit') }}" size="sm" icon="pencil-square">{{ __('Edit') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection

@section('header-actions')
    <flux:button href="{{ url('users/create') }}" variant="primary" size="sm" icon="plus">{{ __('Create user') }}</flux:button>
@endsection
