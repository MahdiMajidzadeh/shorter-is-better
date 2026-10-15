<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\LinkController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\TelegramWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes here must be closure-free: the container warms `route:cache` on
| boot, and closures cannot be serialised.
|
| Every /v1 route needs a bearer API key (Sanctum). The key is bound to one
| project and carries scopes; `ability:` names the scope a route needs.
|
*/

Route::post('telegram/webhook', TelegramWebhookController::class)
    ->withoutMiddleware('throttle:api');

Route::group(['prefix' => 'v1', 'middleware' => ['primary', 'auth:sanctum', 'api.project']], function () {
    Route::get('me', MeController::class);

    Route::get('links', [LinkController::class, 'index'])->middleware('ability:links:read');
    Route::post('links', [LinkController::class, 'store'])->middleware('ability:links:create');
    Route::post('links/bulk', [LinkController::class, 'bulk'])->middleware('ability:links:bulk');
    Route::get('links/{key}', [LinkController::class, 'show'])->middleware('ability:links:read');
    Route::get('links/{key}/stats', [LinkController::class, 'stats'])->middleware('ability:links:stats');
    Route::delete('links/{key}', [LinkController::class, 'destroy'])->middleware('ability:links:delete');

    Route::get('reports/summary', [ReportController::class, 'summary'])->middleware('ability:reports:read');
});
