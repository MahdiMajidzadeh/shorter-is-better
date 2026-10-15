<?php

/** @var Nutgram $bot */

use SergiX44\Nutgram\Nutgram;
use App\Telegram\Conversations\Bulk;
use App\Telegram\Conversations\Feed;
use App\Telegram\Conversations\Stat;
use App\Telegram\Conversations\Short;
use App\Telegram\Handlers\HelpHandler;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Handlers\CancelHandler;
use App\Telegram\Handlers\ReportHandler;
use App\Telegram\Handlers\ProjectHandler;
use App\Telegram\Conversations\ForChannel;
use App\Telegram\Handlers\FallbackHandler;
use App\Telegram\Middleware\ResolveBotChat;
use App\Telegram\Conversations\ShortWithKey;
use App\Telegram\Middleware\EnsureBotAccess;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;

/*
|--------------------------------------------------------------------------
| Telegram handlers
|--------------------------------------------------------------------------
|
| One bot serves every project. ResolveBotChat records the chat and
| EnsureBotAccess refuses anything not approved; /start skips the gate so a
| new user can get their linking url. Command descriptions are what
| `php artisan bot:update` registers with Telegram, per language.
|
*/

$bot->middlewares([ResolveBotChat::class, EnsureBotAccess::class]);

$bot->onCommand('start', StartHandler::class)
    ->skipGlobalMiddlewares([EnsureBotAccess::class])
    ->description(['*' => 'Link this chat to your account', 'fa' => 'اتصال این چت به حساب کاربری']);

$bot->onCommand('short', Short::class)
    ->description(['*' => 'Shorten a url', 'fa' => 'کوتاه کردن یک لینک']);

$bot->onCommand('short_key', ShortWithKey::class)
    ->description(['*' => 'Shorten a url with a custom key', 'fa' => 'کوتاه کردن لینک با کلید دلخواه']);

$bot->onCommand('bulk', Bulk::class)
    ->description(['*' => 'Shorten every url in a text', 'fa' => 'کوتاه کردن همه لینک‌های یک متن']);

$bot->onCommand('for_channel', ForChannel::class)
    ->description(['*' => 'Post an article to a channel', 'fa' => 'ارسال مطلب به کانال']);

$bot->onCommand('feed', Feed::class)
    ->description(['*' => 'Post an article with a feed link to a channel', 'fa' => 'ارسال مطلب همراه لینک فید به کانال']);

$bot->onCommand('stat', Stat::class)
    ->description(['*' => 'Stats for one short link', 'fa' => 'آمار یک لینک کوتاه']);

$bot->onCommand('report', ReportHandler::class)
    ->description(['*' => 'Report for the last 7 days', 'fa' => 'گزارش ۷ روز اخیر']);

$bot->onCommand('project', [ProjectHandler::class, 'list'])
    ->description(['*' => 'Switch the active project', 'fa' => 'تغییر پروژه فعال']);

$bot->onCommand('help', HelpHandler::class)
    ->description(['*' => 'What this bot does', 'fa' => 'راهنما']);

$bot->onCommand('cancel', CancelHandler::class)
    ->description(['*' => 'Cancel the current action', 'fa' => 'لغو عملیات جاری']);

$bot->onCallbackQueryData('project:{id}', [ProjectHandler::class, 'choose']);

$bot->fallbackOn(UpdateType::MESSAGE, FallbackHandler::class);

$bot->onApiError(function (Nutgram $bot, Throwable $e) {
    report($e);

    if ($bot->chatId()) {
        $bot->sendMessage(__('bot.telegram_error', ['error' => $e->getMessage()]));
    }
});
