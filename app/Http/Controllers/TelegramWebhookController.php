<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use SergiX44\Nutgram\Nutgram;

/**
 * Telegram posts every update here. Nutgram validates the secret token in
 * production (see config/nutgram.php) and dispatches to routes/telegram.php.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Nutgram $bot): Response
    {
        $bot->run();

        return response('', 200);
    }
}
