@extends('template.dash')

@section('title','settings')

@section('header','All Setting')

@section('page')
    <div class="mx-auto max-w-4xl">
        @include('template.msg')

        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="lg">Telegram Bot</flux:heading>
                <flux:text class="mt-1">Add a Telegram bot for handy shorter</flux:text>
            </div>
            <flux:card class="lg:col-span-2">
                <flux:text>
                    @if(! is_null($bot))
                        Bot name: <strong>{{ $bot->name }}</strong>
                    @else
                        No bot registered
                    @endif
                </flux:text>
                <flux:separator class="my-4"/>
                <flux:button href="{{ url('settings/bots/create') }}" size="sm" icon="plus">Add bot</flux:button>
            </flux:card>
        </div>

        <flux:separator class="my-10"/>

        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="lg">Telegram Channel</flux:heading>
                <flux:text class="mt-1">To integrate with a Telegram channel</flux:text>
            </div>
            <flux:card class="lg:col-span-2">
                <form method="post" action="{{ url('settings/channel') }}" class="space-y-6">
                    @csrf
                    <flux:radio.group label="Has Channel Signature" name="channel_has" variant="segmented" size="sm">
                        <flux:radio label="Active" value="on" :checked="setting('channel.has', 'off') == 'on'"/>
                        <flux:radio label="Inactive" value="off" :checked="setting('channel.has', 'off') == 'off'"/>
                    </flux:radio.group>
                    <flux:input label="Channel Username" name="channel_username" value="{{ setting('channel.username', '') }}"/>
                    <flux:input label="Channel Id" description="For sending messages to the channel" name="channel_id" value="{{ setting('channel.id', '') }}"/>
                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <flux:separator class="my-10"/>

        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="lg">Home Setting</flux:heading>
                <flux:text class="mt-1">How your home page shows</flux:text>
            </div>
            <flux:card class="lg:col-span-2">
                <form method="post" action="{{ url('settings/home') }}" class="space-y-6">
                    @csrf
                    <flux:input label="Title" name="title" value="{{ setting('home.title', '') }}"/>
                    <flux:input label="Title Accent" name="title-accent" value="{{ setting('home.title-accent', '') }}"/>
                    <flux:input label="Subtitle" name="subtitle" value="{{ setting('home.subtitle', '') }}"/>
                    <flux:input label="CTA Title" description="CTA is necessary" name="cta-title" value="{{ setting('home.cta-title', '') }}"/>
                    <flux:input label="CTA URL" description="CTA URL is necessary, too" name="cta-url" value="{{ setting('home.cta-url', '') }}"/>
                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>
@endsection
