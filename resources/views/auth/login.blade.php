@extends('template.master')

@section('title', __('Sign in'))

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="w-full max-w-sm">
            <div class="mb-10 text-center">
                <img src="{{ versioned_asset('logo.png') }}" class="mx-auto h-12 w-12" alt="{{ config('app.name') }}">
                <flux:heading size="xl" level="1" class="mt-6">{{ __('Sign in to your account') }}</flux:heading>
                <flux:text class="mt-2">{{ __("Let's build some shorts") }}</flux:text>
            </div>

            <form method="post" action="{{ url('auth') }}" class="space-y-6">
                @csrf
                <flux:input label="{{ __('Username') }}" name="username" value="{{ old('username') }}" required/>
                <flux:input label="{{ __('Password') }}" name="password" type="password" autocomplete="current-password" viewable required/>
                <flux:button type="submit" variant="primary" class="w-full">{{ __('Sign in') }}</flux:button>
            </form>
        </div>
    </div>
@endsection
