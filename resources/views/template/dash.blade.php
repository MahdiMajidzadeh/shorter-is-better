@extends('template.master')

@section('content')
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand
                href="{{ url('/panel') }}"
                logo="{{ versioned_asset('logo.png') }}"
                name="{{ config('app.name') }}"
            />
        </flux:sidebar.header>

        @if($projects->isNotEmpty())
            <div class="px-2 pb-2">
                <flux:dropdown class="w-full">
                    <flux:button variant="filled" size="sm" icon="globe-alt" icon:trailing="chevron-down" class="w-full !justify-between">
                        <span class="truncate">{{ $currentProject?->name }}</span>
                    </flux:button>
                    <flux:menu>
                        <flux:menu.heading>{{ __('Project') }}</flux:menu.heading>
                        @foreach($projects as $project)
                            <flux:menu.item href="{{ url('panel/project/'.$project->id) }}" :icon="$project->is($currentProject) ? 'check' : null">
                                {{ $project->name }}
                                <span class="ms-1 text-xs text-zinc-400">{{ $project->domain }}</span>
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            </div>
        @endif

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="{{ url('/panel') }}" :current="request()->is('panel')">
                {{ __('Dashboard') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="link" href="{{ url('/links') }}" :current="request()->is('links*') && ! request()->is('links/bulk', 'links/logs')">
                {{ __('Links') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="queue-list" href="{{ url('/links/bulk') }}" :current="request()->is('links/bulk')">
                {{ __('Bulk shorten') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="document-text" href="{{ url('/links/logs') }}" :current="request()->is('links/logs')">
                {{ __('Visit log') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="key" href="{{ url('/api-keys') }}" :current="request()->is('api-keys*')">
                {{ __('API keys') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>

        @if(auth()->user()->is_admin)
            <flux:sidebar.nav>
                <flux:sidebar.group expandable :expanded="request()->is('projects*', 'channels*', 'users*', 'settings*', 'bot/*')" heading="{{ __('Administration') }}">
                    <flux:sidebar.item icon="globe-alt" href="{{ url('/projects') }}" :current="request()->is('projects*')">
                        {{ __('Projects') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" href="{{ url('/channels') }}" :current="request()->is('channels*')">
                        {{ __('Channels') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" href="{{ url('/users') }}" :current="request()->is('users*')">
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chat-bubble-left-right" href="{{ url('bot/chats') }}" :current="request()->is('bot/chats*')">
                        {{ __('Bot access') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" href="{{ url('settings') }}" :current="request()->is('settings*')">
                        {{ __('Bot') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
        @endif

        <flux:sidebar.spacer/>

        <div class="flex items-center gap-2 px-2 pb-2">
            <form method="post" action="{{ url('auth/logout') }}" class="flex-1">
                @csrf
                <flux:button type="submit" variant="subtle" icon="arrow-right-start-on-rectangle" size="sm" class="w-full !justify-start">
                    {{ __('Sign out') }}
                </flux:button>
            </form>
            <flux:button x-data x-on:click="$flux.dark = ! $flux.dark" variant="subtle" size="sm" icon="moon" aria-label="{{ __('Toggle dark mode') }}"/>
        </div>
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left"/>
        <flux:spacer/>
    </flux:header>

    <flux:main container>
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="xl" level="1">@yield('header')</flux:heading>
            <div class="flex items-center gap-2">
                @yield('header-actions')
            </div>
        </div>
        @yield('page')
    </flux:main>
@endsection
