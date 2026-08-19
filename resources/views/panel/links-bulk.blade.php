@extends('template.dash')

@section('title','Bulk Link')

@section('header','Bulk Link')

@section('page')
    <div class="mx-auto max-w-2xl space-y-8">
        @if (session('converted_text'))
            <flux:card>
                <flux:heading size="lg">Converted Text</flux:heading>
                <flux:text class="mt-1">Every url below has been replaced with its short version</flux:text>
                <div class="mt-6">
                    <flux:textarea rows="10" readonly>{{ session('converted_text') }}</flux:textarea>
                </div>
            </flux:card>
        @endif

        @include('template.msg')

        <flux:card>
            <form method="post" action="{{ url('links/bulk') }}">
                @csrf
                <flux:heading size="lg">Create Bulk Link</flux:heading>
                <flux:text class="mt-1">You can shorten all links found in a text</flux:text>

                <div class="mt-6">
                    <flux:textarea label="Text" name="text" rows="10">{{ old('text') }}</flux:textarea>
                </div>

                <div class="mt-8 flex justify-end">
                    <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
@endsection
