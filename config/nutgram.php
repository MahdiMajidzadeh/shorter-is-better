<?php

return [
    // The Telegram bot API token. One bot serves every project.
    'token' => env('TELEGRAM_TOKEN'),

    // In production the webhook must carry the secret token set by `bot:update`.
    'safe_mode' => env('APP_ENV', 'local') === 'production',

    'config' => [
        // Conversation state lives in the cache; a day is plenty for a chat flow.
        'conversation_ttl' => 86400,
    ],

    // Handlers are registered in routes/telegram.php.
    'routes' => true,

    'mixins' => false,

    'namespace' => app_path('Telegram'),

    'log_channel' => env('TELEGRAM_LOG_CHANNEL', 'null'),
];
