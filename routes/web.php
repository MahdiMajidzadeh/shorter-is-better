<?php

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function() {
    Route::get('/', [C\AuthController::class, 'login']);
    Route::post('/', [C\AuthController::class, 'loginSubmit']);

});

Route::group(['prefix' => 'panel'], function() {
    Route::get('/', [C\PanelController::class, 'index']);

    Route::group(['prefix' => 'links'], function() {
        Route::get('/', [C\LinkController::class, 'create']);
    });
});
