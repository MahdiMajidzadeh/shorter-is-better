@extends('template.master')

@section('title','Hi !')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="mx-auto max-w-3xl text-center">
            <h1 class="text-4xl font-bold tracking-tight text-zinc-800 sm:text-6xl dark:text-white">
                {{ setting('home.title', 'Hi') }}
                <span class="text-accent-content">{{ setting('home.title-accent', 'everyone') }}</span>
            </h1>
            <flux:text size="lg" class="mt-6">
                {{ setting('home.subtitle') }}
            </flux:text>
            <div class="mt-10">
                <flux:button href="{{ setting('home.cta-url', 'https://github.com/MahdiMajidzadeh/shorter-is-better') }}" variant="primary">
                    {{ setting('home.cta-title', 'See Github Ripo') }}
                </flux:button>
            </div>
        </div>
    </div>
@endsection
