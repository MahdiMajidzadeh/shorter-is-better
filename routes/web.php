<?php

//Route::group(['prefix' => 'panel', 'middleware' => 'auth'], function(){
Route::group(['prefix' => 'panel', 'namespace' => 'Panel'], function(){

        Route::get('/','PageController@root');

        Route::get('/links', 'PanelController@linksAll');
        Route::get('/links/create', 'PanelController@linksCreate');
        Route::post('/links/create', 'PanelController@linksCreateSubmit');
        Route::get('/links/{id}', 'PanelController@linksDetail');
    });


Route::get('/', 'RedirectController@root');
Route::get('{slug}', 'RedirectController@link');
