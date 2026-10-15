<?php

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

// Answered on every project domain.
Route::get('/', C\RootController::class);
Route::get(config('shortener.prefix').'/{key}', C\RedirectController::class)->where('key', '[A-Za-z0-9_-]+');

// Telegram mini app: a static shell plus JSON endpoints signed with initData.
Route::group(['prefix' => 'app', 'middleware' => 'primary'], function () {
    Route::get('/', [C\MiniAppController::class, 'shell']);

    Route::group(['prefix' => 'api', 'middleware' => 'miniapp'], function () {
        Route::get('/me', [C\MiniAppController::class, 'me']);
        Route::post('/project', [C\MiniAppController::class, 'switchProject']);
        Route::get('/links', [C\MiniAppController::class, 'links']);
        Route::post('/links', [C\MiniAppController::class, 'createLink']);
        Route::post('/links/bulk', [C\MiniAppController::class, 'bulk']);
        Route::get('/links/{key}/stats', [C\MiniAppController::class, 'stats']);
        Route::get('/report', [C\MiniAppController::class, 'report']);
        Route::post('/posts/preview', [C\MiniAppController::class, 'previewPost']);
        Route::post('/posts/publish', [C\MiniAppController::class, 'publishPost']);
    });
});

Route::group(['prefix' => 'auth', 'middleware' => 'primary'], function () {
    Route::get('/', [C\AuthController::class, 'login'])->name('login');
    Route::post('/', [C\AuthController::class, 'loginSubmit'])->middleware('throttle:5,1');
    Route::post('/logout', [C\AuthController::class, 'logout']);
});

Route::group(['middleware' => ['primary', 'auth', 'project']], function () {
    Route::get('auth/bot/{hash}', [C\AuthController::class, 'hash']);

    Route::get('panel', [C\PanelController::class, 'index']);
    Route::get('panel/project/{project}', [C\ProjectController::class, 'switch']);

    Route::group(['prefix' => 'links'], function () {
        Route::get('/', [C\LinkController::class, 'all']);
        Route::get('/create', [C\LinkController::class, 'create']);
        Route::post('/create', [C\LinkController::class, 'createSubmit']);
        Route::get('/bulk', [C\LinkController::class, 'bulk']);
        Route::post('/bulk', [C\LinkController::class, 'bulkSubmit']);
        Route::get('/logs', [C\LinkController::class, 'logs']);
        Route::post('/delete/{id}', [C\LinkController::class, 'deleteSubmit']);
        Route::get('/{short}', [C\LinkController::class, 'detail']);
    });

    Route::group(['prefix' => 'api-keys'], function () {
        Route::get('/', [C\ApiKeyController::class, 'index']);
        Route::post('/', [C\ApiKeyController::class, 'store']);
        Route::post('/{id}/delete', [C\ApiKeyController::class, 'destroy']);
    });

    Route::group(['middleware' => 'admin'], function () {
        Route::group(['prefix' => 'projects'], function () {
            Route::get('/', [C\ProjectController::class, 'index']);
            Route::get('/create', [C\ProjectController::class, 'create']);
            Route::post('/', [C\ProjectController::class, 'store']);
            Route::get('/{project}/edit', [C\ProjectController::class, 'edit']);
            Route::post('/{project}', [C\ProjectController::class, 'update']);
            Route::post('/{project}/delete', [C\ProjectController::class, 'destroy']);
        });

        Route::group(['prefix' => 'channels'], function () {
            Route::get('/', [C\ChannelController::class, 'index']);
            Route::get('/create', [C\ChannelController::class, 'create']);
            Route::post('/', [C\ChannelController::class, 'store']);
            Route::post('/preview', [C\ChannelController::class, 'preview']);
            Route::get('/{channel}/edit', [C\ChannelController::class, 'edit']);
            Route::post('/{channel}', [C\ChannelController::class, 'update']);
            Route::post('/{channel}/delete', [C\ChannelController::class, 'destroy']);
        });

        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [C\UserController::class, 'index']);
            Route::get('/create', [C\UserController::class, 'create']);
            Route::post('/', [C\UserController::class, 'store']);
            Route::get('/{user}/edit', [C\UserController::class, 'edit']);
            Route::post('/{user}', [C\UserController::class, 'update']);
            Route::post('/{user}/delete', [C\UserController::class, 'destroy']);
        });

        Route::group(['prefix' => 'bot'], function () {
            Route::get('/chats', [C\BotChatController::class, 'index']);
            Route::post('/chats/{chat}', [C\BotChatController::class, 'update']);
            Route::post('/chats/{chat}/delete', [C\BotChatController::class, 'destroy']);
        });

        Route::group(['prefix' => 'settings'], function () {
            Route::get('/', [C\SettingController::class, 'index']);
            Route::post('/bot/register', [C\SettingController::class, 'register']);
        });
    });
});
