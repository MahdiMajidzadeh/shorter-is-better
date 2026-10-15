@extends('template.dash')

@section('title', __('Create link'))

@section('header', __('Create link'))

@section('page')
    <div class="mx-auto max-w-2xl">
        @include('template.msg')

        <flux:card>
            <form method="post" action="{{ url('links/create') }}">
                @csrf
                <flux:heading size="lg">{{ __('Short link') }}</flux:heading>
                <flux:text class="mt-1">{{ __('The link is created on :domain. Leave the key empty to get a random one.', ['domain' => $currentProject->domain]) }}</flux:text>

                <div class="mt-6 space-y-6">
                    <flux:input label="{{ __('Url') }}" name="url" value="{{ old('url') }}" placeholder="https://" dir="ltr"/>
                    <flux:input label="{{ __('Key (optional)') }}" name="key" value="{{ old('key') }}" dir="ltr"/>
                </div>

                <div class="mt-8 flex justify-end gap-2">
                    <flux:button href="{{ url('links') }}" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endsection
