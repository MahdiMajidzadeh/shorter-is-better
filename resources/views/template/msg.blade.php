@foreach ($errors->all() as $message)
    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" class="mb-4" />
@endforeach
@if (session('msg-error'))
    <flux:callout variant="danger" icon="x-circle" heading="Error" class="mb-4">
        <flux:callout.text>{{ session('msg-error') }}</flux:callout.text>
    </flux:callout>
@endif
@if (session('msg-ok'))
    <flux:callout variant="success" icon="check-circle" heading="{{ session('msg-ok') }}" class="mb-4" />
@endif
