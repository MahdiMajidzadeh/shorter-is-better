@extends('template.dash')

@section('title', __('Bot'))

@section('header', __('Telegram bot'))

@section('page')
    <div class="mx-auto max-w-4xl">
        @include('template.msg')

        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="lg">{{ __('Telegram bot') }}</flux:heading>
                <flux:text class="mt-1">{{ __('One bot serves every project. Users link their chat from the bot with /start.') }}</flux:text>
            </div>
            <flux:card class="lg:col-span-2">
                <flux:text>
                    @if(! is_null($bot))
                        {{ __('Bot name') }}: <strong>{{ $bot->name }}</strong>
                    @else
                        {{ __('No bot registered yet.') }}
                    @endif
                </flux:text>
                <flux:separator class="my-4"/>
                <flux:button href="{{ url('settings/bots/create') }}" size="sm" icon="plus">{{ __('Add bot') }}</flux:button>
            </flux:card>
        </div>
    </div>
@endsection
