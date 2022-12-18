@extends('template.dash')

@section('title','settings')

@section('header','All Setting')

@section('page')
    <div class="row mb-10 px-16">
        <div class="col-12">
            @include('template.msg')
        </div>
    </div>
    <div class="row mb-10 px-16">
        <div class="col-lg-4 mb-5 mb-lg-0 pe-lg-16">
            <h4 class="font-semibold mb-2">Telegram Bot</h4>
            <p class="text-sm">Add Telegram bot for handy shorter</p>
        </div>
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-12">
                            <div class="text-black">
                                @if(!is_null($bot))
                                    bot name: {{ $bot->name }}
                                @else
                                    no bot registered
                                @endif
                            </div>
                        </div>
                        <hr>
                        <div class="col-md-12 m-0">
                            @if(!is_null($bot))
                                <a class="btn btn-neutral btn-sm" href="#">Add bot</a>
                            @else
                                <a class="btn btn-neutral btn-sm" href="#">Edit bot</a>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-10 px-16">
        <div class="col-lg-4 mb-5 mb-lg-0 pe-lg-16">
            <h4 class="font-semibold mb-2">Telegram Channel</h4>
            <p class="text-sm">To integrate with Telegram Channel </p>
        </div>
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <form method="post" action="{{ url('settings/channel') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Has Channel Signature:</h6>
                            </div>
                            <div class="col-md-6">
                                <input type="radio" class="btn-check" name="channel_has" id="success-outlined" autocomplete="off"
                                       @if(setting('channel.has', "off") == 'on') checked @endif
                                >
                                <label class="btn btn-outline-info btn-sm" for="success-outlined" value="on">Active</label>

                                <input type="radio" class="btn-check" name="channel_has" id="danger-outlined" autocomplete="off"
                                       @if(setting('channel.has', "off") == 'off') checked @endif
                                >
                                <label class="btn btn-outline-info btn-sm" for="danger-outlined">Inactive</label>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Channel Username:</h6>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="channel_username"
                                       value="{{ setting('channel.username', '') }}">
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Channel Id:</h6>
                                <p class="text-muted text-sm">for send message to channel</p>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="channel_id"
                                       value="{{ setting('channel.id', '') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer pt-0">
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row mb-10 px-16">
        <div class="col-lg-4 mb-5 mb-lg-0 pe-lg-16">
            <h4 class="font-semibold mb-2">Home Setting</h4>
            <p class="text-sm">How Your Home Page Show</p>
        </div>
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <form method="post" action="{{ url('settings/home') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Title:</h6>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="title"
                                       value="{{ setting('home.title', '') }}">
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Title Accent:</h6>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="title-accent"
                                       value="{{ setting('home.title-accent', '') }}">
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Subtitle:</h6>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="subtitle"
                                       value="{{ setting('home.subtitle', '') }}">
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">CTA Title:</h6>
                                <p class="text-sm">CTA is necessary</p>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="cta-title"
                                       value="{{ setting('home.cta-title', '') }}">
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">CTA URL:</h6>
                                <p class="text-sm">CTA URL is necessary, too</p>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="cta-url"
                                       value="{{ setting('home.cta-url', '') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer pt-0">
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row mb-10 px-16">
        <div class="col-lg-4 mb-5 mb-lg-0 pe-lg-16">
            <h4 class="font-semibold mb-2">Telescope</h4>
            <p class="text-sm">For Debugging</p>
        </div>
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-12">
                            <a class="btn btn-neutral btn-sm" href="#">Active</a>
                            <a class="btn btn-neutral btn-sm" href="#">Purge</a>
                            <a class="btn btn-neutral btn-sm" href="#">Open</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
