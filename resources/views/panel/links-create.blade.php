@extends('template.dash')

@section('title','create links')

@section('header','Create Link')

@section('page')
    <div class="mx-auto max-w-2xl">
        @include('template.msg')

        <flux:card>
            <form method="post" action="{{ url('links/create') }}">
                @csrf
                <flux:heading size="lg">Short Url</flux:heading>
                <flux:text class="mt-1">You can set a custom key for your shortened url</flux:text>

                <div class="mt-6 space-y-6">
                    <flux:input label="Url" name="url" value="{{ old('url') }}" placeholder="https://"/>
                    <flux:input label="Key (optional)" name="key" value="{{ old('key') }}"/>
                </div>

                <div class="mt-8 flex justify-end gap-2">
                    <flux:button href="{{ url('links') }}" variant="ghost" size="sm">Cancel</flux:button>
                    <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endsection
