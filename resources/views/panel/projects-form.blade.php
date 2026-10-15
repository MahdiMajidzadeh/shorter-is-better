@extends('template.dash')

@section('title', $project->exists ? __('Edit project') : __('Create project'))

@section('header', $project->exists ? $project->name : __('Create project'))

@section('page')
    <div class="mx-auto max-w-2xl space-y-6">
        @include('template.msg')

        <form method="post" action="{{ $project->exists ? url('projects/'.$project->id) : url('projects') }}" class="space-y-6">
            @csrf
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Project') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('One project is one short domain. Point the domain at this server and add it to the app in Dokploy before using it.') }}</flux:text>
                </div>
                <flux:input label="{{ __('Name') }}" name="name" value="{{ old('name', $project->name) }}"/>
                <flux:input label="{{ __('Domain') }}" description="{{ __('Host only, for example s.example.com') }}" name="domain" value="{{ old('domain', $project->domain) }}" dir="ltr"/>
                <flux:input label="{{ __('Root redirect') }}" description="{{ __('Where visitors of the bare domain are sent') }}" name="root_redirect_url" value="{{ old('root_redirect_url', $project->root_redirect_url) }}" placeholder="https://" dir="ltr"/>
            </flux:card>

            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Channels') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Channels the bot may post to from this project. Pick the one used when no channel is chosen.') }}</flux:text>
                </div>
                @php
                    $attached = old('channels', $project->exists ? $project->channels->pluck('id')->all() : []);
                    $defaultId = old('default_channel', $project->exists ? optional($project->channels->firstWhere('pivot.is_default', true))->id : null);
                @endphp
                @forelse($channels as $channel)
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-100 py-2 last:border-0 dark:border-zinc-700">
                        <flux:checkbox name="channels[]" value="{{ $channel->id }}" label="{{ $channel->title }}" description="{{ $channel->username ?: $channel->chat_id }}" :checked="in_array($channel->id, $attached)"/>
                        <label class="flex items-center gap-2 text-sm text-zinc-500">
                            <input type="radio" name="default_channel" value="{{ $channel->id }}" @checked($defaultId == $channel->id)>
                            {{ __('Default') }}
                        </label>
                    </div>
                @empty
                    <flux:text size="sm">{{ __('No channels yet.') }} <flux:link href="{{ url('channels/create') }}">{{ __('Create one') }}</flux:link></flux:text>
                @endforelse
            </flux:card>

            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Users') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Admins see every project. Grant other users access here.') }}</flux:text>
                </div>
                @php $granted = old('users', $project->exists ? $project->users->pluck('id')->all() : []); @endphp
                @forelse($users as $user)
                    <flux:checkbox name="users[]" value="{{ $user->id }}" label="{{ $user->name }}" description="{{ $user->username }}" :checked="in_array($user->id, $granted)"/>
                @empty
                    <flux:text size="sm">{{ __('No non-admin users yet.') }}</flux:text>
                @endforelse
            </flux:card>

            <div class="flex justify-end gap-2">
                <flux:button href="{{ url('projects') }}" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
            </div>
        </form>

        @if($project->exists)
            <flux:card>
                <flux:heading size="lg">{{ __('Delete project') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Only a project without links can be deleted.') }}</flux:text>
                <form method="post" action="{{ url('projects/'.$project->id.'/delete') }}" class="mt-4" onsubmit="return confirm(@json(__('Delete this project?')))">
                    @csrf
                    <flux:button type="submit" variant="danger" size="sm" icon="trash">{{ __('Delete') }}</flux:button>
                </form>
            </flux:card>
        @endif
    </div>
@endsection
