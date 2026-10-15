<?php

namespace App\Support;

use SergiX44\Nutgram\Nutgram;

/**
 * Registers the webhook (with the secret token Nutgram expects in safe
 * mode) and the per-language command list. Used by the settings page and
 * `php artisan bot:update`.
 */
class BotRegistrar
{
    public function __construct(private Nutgram $bot) {}

    public static function webhookUrl(): string
    {
        return rtrim(config('app.url'), '/').'/api/telegram/webhook';
    }

    public function register(): void
    {
        $this->bot->setWebhook(
            url: static::webhookUrl(),
            secret_token: config('nutgram.safe_mode') ? md5(config('app.key')) : null,
            allowed_updates: ['message', 'callback_query'],
        );

        $this->bot->registerMyCommands();
    }
}
