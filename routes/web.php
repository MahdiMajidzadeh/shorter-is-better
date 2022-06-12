<?php

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

Route::view('/', 'index');

Route::group(['prefix' => 'auth'], function() {
    Route::get('/', [C\AuthController::class, 'login']);
    Route::post('/', [C\AuthController::class, 'loginSubmit']);
});

Route::group(['middleware' => 'auth'], function() {
    Route::group(['prefix' => 'panel'], function() {
        Route::get('/', [C\PanelController::class, 'index']);
    });

    Route::group(['prefix' => 'links'], function() {
        Route::get('/', [C\LinkController::class, 'all']);
        Route::get('/create', [C\LinkController::class, 'create']);
        Route::post('/create', [C\LinkController::class, 'createSubmit']);

        Route::get('/{short}', [C\LinkController::class, 'detail']);
    });
});