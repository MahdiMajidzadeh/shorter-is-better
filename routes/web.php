<?php

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

Route::view('/', 'index');

Route::group(['prefix' => 'auth'], function() {
    Route::get('/', [C\AuthController::class, 'login'])->name('login');
    Route::post('/', [C\AuthController::class, 'loginSubmit']);
    Route::post('/logout', [C\AuthController::class, 'logout']);
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

    Route::group(['prefix' => 'settings'], function() {
        Route::get('/', [C\SettingController::class, 'index']);
        Route::get('/tokens', [C\SettingController::class, 'tokens']);
        Route::get('/tokens/create', [C\SettingController::class, 'tokensCreateSubmit']);

        Route::get('/bots', [C\SettingController::class, 'bots']);
        Route::get('/bots/create', [C\SettingController::class, 'botsCreate']);
        Route::post('/bots/create', [C\SettingController::class, 'botsCreateSubmit']);
    });
});