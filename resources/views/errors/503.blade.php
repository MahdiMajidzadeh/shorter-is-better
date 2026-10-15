@extends('errors.layout', ['code' => 503])

@section('heading', __('Temporarily unavailable'))

@section('message', __('The service is being updated. Try again shortly.'))
