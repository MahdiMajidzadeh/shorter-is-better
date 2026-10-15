@extends('template.dash')

@section('title', __('Bulk shorten'))

@section('header', __('Bulk shorten'))

@section('page')
    <div class="mx-auto max-w-2xl space-y-8">
        @if (session('converted_text'))
            <flux:card>
                <flux:heading size="lg">{{ __('Converted text') }}</flux:heading>
                <flux:text class="mt-1">{{ __(':count links were shortened and replaced in the text below.', ['count' => local_number(session('converted_count', 0))]) }}</flux:text>
                <div class="mt-6">
                    <flux:textarea rows="10" readonly>{{ session('converted_text') }}</flux:textarea>
                </div>
            </flux:card>
        @endif

        @include('template.msg')

        <flux:card>
            <form method="post" action="{{ url('links/bulk') }}">
                @csrf
                <flux:heading size="lg">{{ __('Shorten every link in a text') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Paste any text. Each url in it is replaced with a short link on :domain.', ['domain' => $currentProject->domain]) }}</flux:text>
                <div class="mt-6">
                    <flux:textarea label="{{ __('Text') }}" name="text" rows="10">{{ old('text') }}</flux:textarea>
                </div>
                <div class="mt-8 flex justify-end">
                    <flux:button type="submit" variant="primary" size="sm">{{ __('Shorten') }}</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endsection
