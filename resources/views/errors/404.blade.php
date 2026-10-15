@extends('errors.layout', ['code' => 404])

@section('heading', __('Page not found'))

@section('message', __('The link may be wrong, or the page was removed.'))
