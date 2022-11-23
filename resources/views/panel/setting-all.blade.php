@extends('template.dash')

@section('title','settings')

@section('header','All Setting')

@section('page')
    <div class="">
        <div class="container py-7">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    @include('template.msg')
                    <form method="post" action="{{ url('settings') }}">
                        @csrf
                        <div class="row align-items-center my-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Has Channel</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="channel_has"
                                           id="switch_make_public" @if(setting('channel.has', false)) checked @endif>
                                    <label class="form-check-label ms-2" for="switch_make_public"></label>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center my-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Channel Username</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="">
                                    <input type="text" class="form-control" name="channel_username"
                                           value="{{ setting('channel.username', '') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center my-3">
                            <div class="col-md-4">
                                <h6 class="mb-0 font-semibold">Channel Id</h6>
                                <p class="text-muted text-sm">for send message to channel</p>
                            </div>
                            <div class="col-md-6">
                                <div class="">
                                    <input type="text" class="form-control" name="channel_id"
                                           value="{{ setting('channel.id', '') }}">
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                    <hr class="my-6"/>
                    <div class="row align-items-center my-3">
                        <div class="col-md-4">
                            <h6 class="mb-0 font-semibold">Bot: </h6>
                        </div>
                        <div class="col-md-6">
                            @if(!is_null($bot))
                                bot active: {{ $bot->name }}
                            @else
                                bot inactive:
                                <a href="{{ url('settings/bots/create') }}" class="btn btn-primary btn-sm">
                                    Create Bot
                                </a>
                            @endif
                        </div>
                    </div>
                    <hr class="my-6"/>
                    <div class="row align-items-center my-3">
                        <div class="col-md-4">
                            <h6 class="mb-0 font-semibold">Telescope: </h6>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ url('telescope') }}" class="btn btn-info rounded-pill btn-sm">
                                Open Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
