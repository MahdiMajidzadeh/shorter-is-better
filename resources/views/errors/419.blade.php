@extends('errors.layout', ['code' => 419])

@section('heading', __('The page expired'))

@section('message', __('Your session timed out. Go back and try again.'))
