<?php

use DefStudio\Telegraph\Telegraph;

return [
    /*
     * Telegram api base url, it can be overridden
     * for self-hosted servers
     */
    'telegram_api_url'                => 'https://api.telegram.org/',

    /*
     * Sets Telegraph messages default parse mode
     * allowed values: html|markdown|MarkdownV2
     */
    'default_parse_mode'              => Telegraph::PARSE_HTML,

    /*
     * Sets the handler to be used when Telegraph
     * receives a new webhook call.
     *
     * For reference, see https://defstudio.github.io/telegraph/webhooks/overview
     */
    'webhook_handler'                 => App\Http\Webhooks\Handler::class,

    /*
     * Sets a custom domain when registering a webhook. This will allow a local telegram bot api server
     * to reach the webhook. Disabled by default
     *
     * For reference, see https://core.telegram.org/bots/api#using-a-local-bot-api-server
     */
    // 'custom_webhook_domain' => 'http://my.custom.domain';

    /*
     * If enabled, Telegraph dumps received
     * webhook messages to logs
     */
    'debug_mode'                      => false,

    /*
     * If enabled, unknown webhook commands are
     * reported as exception in application logs
     */
    'report_unknown_webhook_commands' => true,

    'security'                        => [
        /*
         * if enabled, allows callback queries from unregistered chats
         */
        'allow_callback_queries_from_unknown_chats' => false,

        /*
         * if enabled, allows messages and commands from unregistered chats
         */
        'allow_messages_from_unknown_chats'         => true,

        /*
         * if enabled, store unknown chats as new TelegraphChat models
         */
        'store_unknown_chats_in_db'                 => true,
    ],

    /*
     * Set model class for both TelegraphBot and TelegraphChat,
     * to allow more customization.
     *
     * Bot model must be or extend `DefStudio\Telegraph\Models\TelegraphBot::class`
     * Chat model must be or extend `DefStudio\Telegraph\Models\TelegraphChat::class`
     */
    'models'                          => [
        'bot'  => DefStudio\Telegraph\Models\TelegraphBot::class,
        'chat' => DefStudio\Telegraph\Models\TelegraphChat::class,
    ],

    'storage'                         => [
        /**
         * Default storage driver to be used for Telegraph data
         */
        'default' => 'file',

        'stores'  => [
            'file'  => [
                /**
                 * Telegraph cache driver to be used, must implement
                 * DefStudio\Telegraph\Contracts\StorageDriver contract
                 */
                'driver' => \DefStudio\Telegraph\Storage\FileStorageDriver::class,

                /*
                 * Laravel Storage disk to use. See /config/filesystems/disks for available disks
                 * If 'null', Laravel default store will be used,
                 */
                'disk'   => 'local',

                /**
                 * Folder inside filesystem to be used as root for Telegraph storage
                 */
                'root'   => 'telegraph',
            ],
            'cache' => [
                /**
                 * Telegraph cache driver to be used, must implement
                 * DefStudio\Telegraph\Contracts\StorageDriver contract
                 */
                'driver'     => \DefStudio\Telegraph\Storage\CacheStorageDriver::class,

                /*
                 * Laravel Cache store to use. See /config/cache/stores for available stores
                 * If 'null', Laravel default store will be used,
                 */
                'store'      => null,

                /*
                 * Prefix to be prepended to cache keys
                 */
                'key_prefix' => 'tgph',
            ],
        ],
    ],
];
