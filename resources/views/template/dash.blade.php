@extends('template.master')

@section('content')
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand
                href="{{ url('/panel') }}"
                logo="{{ asset('logo.png') }}"
                logo:dark="{{ asset('logo-2.png') }}"
                name="Shorter"
            />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="{{ url('/panel') }}" :current="request()->is('panel')">
                Dashboard
            </flux:sidebar.item>
            <flux:sidebar.item icon="link" href="{{ url('/links') }}" :current="request()->is('links*') && ! request()->is('links/bulk', 'links/logs')">
                Links
            </flux:sidebar.item>
            <flux:sidebar.item icon="queue-list" href="{{ url('/links/bulk') }}" :current="request()->is('links/bulk')">
                Bulk Link
            </flux:sidebar.item>
            <flux:sidebar.item icon="document-text" href="{{ url('/links/logs') }}" :current="request()->is('links/logs')">
                Visit Logs
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <flux:sidebar.spacer/>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="cog-6-tooth" href="{{ url('settings') }}" :current="request()->is('settings*')">
                Setting
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <div class="flex items-center gap-2 px-2 pb-2">
            <form method="post" action="{{ url('auth/logout') }}" class="flex-1">
                @csrf
                <flux:button type="submit" variant="subtle" icon="arrow-right-start-on-rectangle" size="sm" class="w-full !justify-start">
                    Logout
                </flux:button>
            </form>
            <flux:button x-data x-on:click="$flux.dark = ! $flux.dark" variant="subtle" size="sm" icon="moon" aria-label="Toggle dark mode"/>
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
