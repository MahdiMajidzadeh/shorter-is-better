@extends('template.dash')

@section('title','create bots')

@section('header','Create Bots')

@section('page')
    <div class="mx-auto max-w-2xl">
        @include('template.msg')

        <flux:card>
            <form method="post" action="{{ url('settings/bots/create') }}">
                @csrf
                <flux:heading size="lg">Telegram Bot</flux:heading>
                <flux:text class="mt-1">Create a bot with @@BotFather and paste the token here — the webhook and command list are registered for you</flux:text>

                <div class="mt-6 space-y-6">
                    <flux:input label="Name" name="name" value="{{ old('name') }}"/>
                    <flux:input label="Token" name="token" value="{{ old('token') }}" viewable type="password"/>
                </div>

                <div class="mt-8 flex justify-end">
                    <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endsection
