@extends('template.dash')

@section('title', __('Projects'))

@section('header', __('Projects'))

@section('page')
    @include('template.msg')

    <flux:card class="!p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Domain') }}</flux:table.column>
                <flux:table.column>{{ __('Root redirect') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Links') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Channels') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Users') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($allProjects as $project)
                    <flux:table.row :key="$project->id">
                        <flux:table.cell class="font-medium">{{ $project->name }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-sm" dir="ltr">{{ $project->domain }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs truncate" dir="ltr">{{ $project->root_redirect_url }}</flux:table.cell>
                        <flux:table.cell align="end">{{ local_number($project->links_count) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ local_number($project->channels_count) }}</flux:table.cell>
                        <flux:table.cell align="end">{{ local_number($project->users_count) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button href="{{ url('projects/'.$project->id.'/edit') }}" size="sm" icon="pencil-square">{{ __('Edit') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">{{ __('No projects yet. Create one to start shortening.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
@endsection

@section('header-actions')
    <flux:button href="{{ url('projects/create') }}" variant="primary" size="sm" icon="plus">{{ __('Create project') }}</flux:button>
@endsection
