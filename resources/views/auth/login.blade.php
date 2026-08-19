@extends('template.master')

@section('title','login')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="w-full max-w-sm">
            <div class="mb-10 text-center">
                <img src="{{ asset('favicon.png') }}" class="mx-auto h-12 w-12" alt="Shorter Is Better">
                <flux:heading size="xl" level="1" class="mt-6">Sign in to your account</flux:heading>
                <flux:text class="mt-2">Let's build some shorts</flux:text>
            </div>

            <form method="post" action="{{ url('auth') }}" class="space-y-6">
                @csrf
                <flux:input label="Username" name="username" value="{{ old('username') }}" required/>
                <flux:input label="Password" name="password" type="password" autocomplete="current-password" viewable required/>
                <flux:button type="submit" variant="primary" class="w-full">Sign in</flux:button>
            </form>
        </div>
    </div>
@endsection
