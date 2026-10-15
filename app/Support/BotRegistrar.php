<?php

namespace App\Support;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\WebApp\WebAppInfo;
use SergiX44\Nutgram\Telegram\Types\Command\MenuButtonWebApp;

/**
 * Registers the webhook (with the secret token Nutgram expects in safe
 * mode), the per-language command list and the mini app menu button. Used by the settings page and
 * `php artisan bot:update`.
 */
class BotRegistrar
{
    public function __construct(private Nutgram $bot) {}

    public static function webhookUrl(): string
    {
        return rtrim(config('app.url'), '/').'/api/telegram/webhook';
    }

    public static function miniAppUrl(): string
    {
        return rtrim(config('app.url'), '/').'/app';
    }

    public function register(): void
    {
        $this->bot->setWebhook(
            url: static::webhookUrl(),
            secret_token: config('nutgram.safe_mode') ? md5(config('app.key')) : null,
            allowed_updates: ['message', 'callback_query'],
        );

        $this->bot->registerMyCommands();

        // The menu button next to the message box opens the mini app.
        $this->bot->setChatMenuButton(menu_button: new MenuButtonWebApp(
            text: __('miniapp.title'),
            web_app: WebAppInfo::make(static::miniAppUrl()),
        ));
    }
}
