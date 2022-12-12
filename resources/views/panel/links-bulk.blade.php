@extends('template.dash')

@section('title','Bulk Link')

@section('header','Bulk Link')

@section('page')
    <div class="row">
        <div class="col-lg-7 mx-auto">
            <div class="card shadow border-0 mb-10">
                @include('template.msg')
                <form method="post" action="{{ url('links/bulk') }}">
                    @csrf
                    <div class="card-body">
                        <div class="mb-7">
                            <h4 class="font-semibold mb-1">Create Bulk Link</h4>
                            <p class="text-sm text-muted">you can short all link form texts</p>
                        </div>
                        <div class="row g-5">
                            <div class="col-md-12">
                                <div class="">
                                    <label class="form-label" for="text">Text:</label>
                                    <textarea class="form-control" id="text" name="text" rows="10"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end py-4">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
