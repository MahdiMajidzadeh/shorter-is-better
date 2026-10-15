@extends('template.dash')

@section('title', __('Links'))

@section('header', __('Links'))

@section('page')
    @include('template.msg')

    <flux:card class="!p-4">
        <flux:table :paginate="$links">
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Short link') }}</flux:table.column>
                <flux:table.column>{{ __('Source') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Clicks') }}</flux:table.column>
                <flux:table.column>{{ __('Created') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($links as $link)
                    <flux:table.row :key="$link->id">
                        <flux:table.cell class="max-w-xs truncate">
                            <div class="truncate font-medium">{{ $link->displayTitle() }}</div>
                            <flux:link href="{{ $link->destination_url }}" variant="subtle" class="block truncate !text-xs" dir="ltr">
                                {{ $link->destination_url }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:link href="{{ $link->default_short_url }}" class="font-mono text-sm" dir="ltr">
                                /s/{{ $link->url_key }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ __('source.'.$link->source) }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums">{{ local_number($link->human_visits_count) }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ local_date($link->created_at) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button href="{{ url('links/'. $link->url_key) }}" size="sm" icon="eye">{{ __('View') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection

@section('header-actions')
    <flux:button href="{{ url('links/create') }}" variant="primary" size="sm" icon="plus">{{ __('Create link') }}</flux:button>
@endsection
