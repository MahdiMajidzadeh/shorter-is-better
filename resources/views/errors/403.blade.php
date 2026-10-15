@extends('errors.layout', ['code' => 403])

@section('heading', __('You are not allowed to do this.'))

@section('message', __('This page needs a role your account does not have.'))
