@extends('template.dash')

@section('title','create links')

@section('header','Create Link')

@section('page')
    <div class="row">
        <div class="col-lg-7 mx-auto">
            <div class="card shadow border-0 mb-10">
                @include('template.msg')
                <form method="post" action="{{ url('links/create') }}">
                    @csrf
                    <div class="card-body">
                        <div class="mb-7">
                            <h4 class="font-semibold mb-1">Short Url</h4>
                            <p class="text-sm text-muted">you can set custom key for your shorten url</p>
                        </div>
                        <div class="row g-5">
                            <div class="col-md-12">
                                <div class="">
                                    <label class="form-label" for="url">Url:</label>
                                    <input type="text" class="form-control" id="url" name="url" placeholder="https://">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="">
                                    <label class="form-label" for="key">key (optional)</label>
                                    <input type="text" class="form-control" id="key" name="key">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end py-4">
                        <button type="button" class="btn btn-sm btn-neutral me-2">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
