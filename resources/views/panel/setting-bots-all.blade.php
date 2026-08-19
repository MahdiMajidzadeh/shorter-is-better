@extends('template.dash')

@section('title','all bots')

@section('header','Bots')

@section('page')
    <flux:card class="!p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Id</flux:table.column>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($bots as $bot)
                    <flux:table.row :key="$bot->id">
                        <flux:table.cell>{{ $bot->id }}</flux:table.cell>
                        <flux:table.cell>{{ $bot->name }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="trash" aria-label="Delete bot"/>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection

@section('header-actions')
    <flux:button href="{{ url('settings/bots/create') }}" variant="primary" size="sm" icon="plus">Create bot</flux:button>
@endsection
