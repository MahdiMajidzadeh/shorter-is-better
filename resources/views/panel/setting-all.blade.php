@extends('template.dash')

@section('title', __('Bot'))

@section('header', __('Telegram bot'))

@section('page')
    <div class="mx-auto max-w-4xl">
        @include('template.msg')

        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="lg">{{ __('Telegram bot') }}</flux:heading>
                <flux:text class="mt-1">{{ __('One bot serves every project. Its token is the TELEGRAM_TOKEN environment variable. Users link their chat with /start and you approve them under Bot access.') }}</flux:text>
            </div>
            <flux:card class="space-y-4 lg:col-span-2">
                @if(! $configured)
                    <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ __('TELEGRAM_TOKEN is not set.') }}">
                        @if($legacyToken)
                            <flux:callout.text>{{ __('The token of the bot created in the previous version is shown below. Put it in the environment as TELEGRAM_TOKEN and restart the app.') }}</flux:callout.text>
                            <flux:input value="{{ $legacyToken }}" readonly copyable dir="ltr" class="mt-3"/>
                        @endif
                    </flux:callout>
                @elseif($error)
                    <flux:callout variant="danger" icon="x-circle" heading="{{ __('Telegram did not accept the token.') }}">
                        <flux:callout.text dir="ltr">{{ $error }}</flux:callout.text>
                    </flux:callout>
                @else
                    <flux:text>{{ __('Bot') }}: <strong dir="ltr">@{{ $botUsername }}</strong></flux:text>
                @endif

                <flux:input label="{{ __('Webhook url') }}" value="{{ $webhookUrl }}" readonly copyable dir="ltr"/>

                <form method="post" action="{{ url('settings/bot/register') }}">
                    @csrf
                    <flux:button type="submit" variant="primary" size="sm" icon="arrow-path" :disabled="! $configured">{{ __('Register webhook and commands') }}</flux:button>
                </form>
                <flux:text size="sm">{{ __('Run this once after setting the token and again whenever the app url changes. The same thing happens with php artisan bot:update.') }}</flux:text>
            </flux:card>
        </div>
    </div>
@endsection
