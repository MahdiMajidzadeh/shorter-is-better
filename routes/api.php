<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes here must be closure-free: the container warms `route:cache` on
| boot, and closures cannot be serialised.
|
*/

Route::post('telegram/webhook', TelegramWebhookController::class)
    ->withoutMiddleware('throttle:api');
