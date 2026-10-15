@extends('template.dash')

@section('title', __('Add bot'))

@section('header', __('Add bot'))

@section('page')
    <div class="mx-auto max-w-2xl">
        @include('template.msg')

        <flux:card>
            <form method="post" action="{{ url('settings/bots/create') }}">
                @csrf
                <flux:heading size="lg">{{ __('Telegram bot') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Create a bot with @BotFather and paste the token here. The webhook and command list are registered for you.') }}</flux:text>

                <div class="mt-6 space-y-6">
                    <flux:input label="{{ __('Name') }}" name="name" value="{{ old('name') }}"/>
                    <flux:input label="{{ __('Token') }}" name="token" value="{{ old('token') }}" viewable type="password" dir="ltr"/>
                </div>

                <div class="mt-8 flex justify-end">
                    <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endsection
