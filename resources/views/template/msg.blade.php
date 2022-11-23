@foreach ($errors->all() as $message)
    <div class="alert alert-danger" role="alert">
        <div class="ps-10">
            {{ $message }}
        </div>
    </div>
@endforeach
@if (session('msg-error'))
    <div class="alert alert-danger mb-3" role="alert">
        <div class="d-flex align-items-center">
            <div class="w-8 text-lg">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <span class="font-bold">Error:</span>
        </div>
        <div class="ps-10">
            {{ session('msg-error') }}
        </div>
    </div>
@endif
@if (session('msg-ok'))
    <div class="alert alert-success" role="alert">
        <div class="d-flex align-items-center">
            <div class="w-8 text-lg">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <span class="font-bold">{{ session('msg-ok') }}</span>
        </div>
    </div>
@endif