@extends('errors.layout', ['code' => 429])

@section('heading', __('Too many requests'))

@section('message', __('Slow down a little and try again in a minute.'))
