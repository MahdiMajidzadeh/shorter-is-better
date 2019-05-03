<?php

//Route::group(['prefix' => 'panel', 'middleware' => 'auth'], function(){
Route::group(['prefix' => 'panel', 'namespace' => 'Panel'], function(){

    Route::get('/','PageController@root');

    Route::get('/links', 'LinkController@all');
    Route::get('/links/create', 'LinkController@create');
    Route::post('/links/create', 'LinkController@createSubmit');
    Route::get('/links/{id}', 'LinkController@detail');

    Route::get('/users', 'UserController@root');
    Route::post('/users/create', 'UserController@createSubmit');

    Route::get('/setting/', 'SettingController@root');
    Route::post('/setting/', 'SettingController@rootSubmit');
});

Route::group(['prefix' => 'auth'], function() {

    Route::get('/','AuthController@login');

    Route::get('/logout', 'AuthController@logout');
});


Route::get('/', 'RedirectController@root');
Route::get('{slug}', 'RedirectController@link');
