@extends('template.dash')

@section('title', $user->exists ? __('Edit user') : __('Create user'))

@section('header', $user->exists ? $user->name : __('Create user'))

@section('page')
    <div class="mx-auto max-w-2xl space-y-6">
        @include('template.msg')

        @php $self = $user->exists && $user->id === auth()->id(); @endphp

        <form method="post" action="{{ $user->exists ? url('users/'.$user->id) : url('users') }}" class="space-y-6">
            @csrf
            <flux:card class="space-y-6">
                <flux:input label="{{ __('Name') }}" name="name" value="{{ old('name', $user->name) }}"/>
                <flux:input label="{{ __('Username') }}" name="username" value="{{ old('username', $user->username) }}" dir="ltr"/>
                <flux:input label="{{ __('Password') }}" description="{{ $user->exists ? __('Leave empty to keep the current password') : '' }}" name="password" type="password" viewable autocomplete="new-password" dir="ltr"/>
                <flux:radio.group label="{{ __('Language') }}" name="locale" variant="segmented" size="sm">
                    <flux:radio label="{{ __('language.fa') }}" value="fa" :checked="old('locale', $user->locale) === 'fa'"/>
                    <flux:radio label="{{ __('language.en') }}" value="en" :checked="old('locale', $user->locale) === 'en'"/>
                </flux:radio.group>
                <input type="hidden" name="is_admin" value="0">
                <flux:checkbox name="is_admin" value="1" label="{{ __('Admin') }}" description="{{ __('Sees every project and manages users, channels and the bot') }}" :checked="old('is_admin', $user->is_admin)" :disabled="$self"/>
                <input type="hidden" name="is_active" value="0">
                <flux:checkbox name="is_active" value="1" label="{{ __('Active') }}" description="{{ __('Disabled users cannot sign in or use the bot') }}" :checked="old('is_active', $user->is_active)" :disabled="$self"/>
            </flux:card>

            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Projects') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Ignored for admins, who see every project.') }}</flux:text>
                </div>
                @php $granted = old('projects', $user->exists ? $user->projects->pluck('id')->all() : []); @endphp
                @forelse($allProjects as $project)
                    <flux:checkbox name="projects[]" value="{{ $project->id }}" label="{{ $project->name }}" description="{{ $project->domain }}" :checked="in_array($project->id, $granted)"/>
                @empty
                    <flux:text size="sm">{{ __('No projects yet.') }}</flux:text>
                @endforelse
            </flux:card>

            <div class="flex justify-end gap-2">
                <flux:button href="{{ url('users') }}" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
            </div>
        </form>

        @if($user->exists && ! $self)
            <flux:card>
                <flux:heading size="lg">{{ __('Delete user') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Links they created stay. Their Telegram chat is unlinked.') }}</flux:text>
                <form method="post" action="{{ url('users/'.$user->id.'/delete') }}" class="mt-4" onsubmit="return confirm(@json(__('Delete this user?')))">
                    @csrf
                    <flux:button type="submit" variant="danger" size="sm" icon="trash">{{ __('Delete') }}</flux:button>
                </form>
            </flux:card>
        @endif
    </div>
@endsection
