@extends('template.master')

@section('title', $code)

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="max-w-md text-center">
            <div class="font-mono text-6xl font-bold text-zinc-300 dark:text-zinc-600" dir="ltr">{{ $code }}</div>
            <flux:heading size="xl" level="1" class="mt-6">@yield('heading')</flux:heading>
            <flux:text class="mt-2">@yield('message')</flux:text>
            <div class="mt-8">
                <flux:button href="{{ url('panel') }}" variant="primary" size="sm">{{ __('Back to the panel') }}</flux:button>
            </div>
        </div>
    </div>
@endsection
