<?php

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

Route::view('/', 'index');

Route::group(['prefix' => 'auth'], function () {
    Route::get('/', [C\AuthController::class, 'login'])->name('login');
    Route::post('/', [C\AuthController::class, 'loginSubmit']);
    Route::post('/logout', [C\AuthController::class, 'logout']);
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('auth/bot/{hash}', [C\AuthController::class, 'hash']);
    Route::group(['prefix' => 'panel'], function () {
        Route::get('/', [C\PanelController::class, 'index']);
    });

    Route::group(['prefix' => 'links'], function () {
        Route::get('/', [C\LinkController::class, 'all']);
        Route::get('/create', [C\LinkController::class, 'create']);
        Route::post('/create', [C\LinkController::class, 'createSubmit']);
        Route::get('/bulk', [C\LinkController::class, 'bulk']);
        Route::post('/bulk', [C\LinkController::class, 'bulkSubmit']);
        Route::get('/logs', [C\LinkController::class, 'logs']);
        Route::get('/delete/{id}', [C\LinkController::class, 'deleteSubmit']);
        Route::get('/{short}', [C\LinkController::class, 'detail']);
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [C\SettingController::class, 'index']);
        Route::post('/', [C\SettingController::class, 'indexSubmit']);
        Route::get('/bots/create', [C\SettingController::class, 'botsCreate']);
        Route::post('/bots/create', [C\SettingController::class, 'botsCreateSubmit']);
    });
});
