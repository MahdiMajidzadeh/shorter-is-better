@extends('template.dash')

@section('title', __('No project'))

@section('header', __('No project'))

@section('page')
    <flux:card class="py-16 text-center">
        <flux:icon.globe-alt class="mx-auto size-10 text-zinc-400"/>
        <flux:heading size="lg" class="mt-4">{{ __('You have no project yet') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Ask an admin to grant you access to a project. Links, reports and the bot all work inside a project.') }}</flux:text>
    </flux:card>
@endsection
