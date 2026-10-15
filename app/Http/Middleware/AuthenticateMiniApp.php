<?php

namespace App\Http\Middleware;

use Closure;
use Throwable;
use App\Models\BotChat;
use App\Models\Project;
use Illuminate\Http\Request;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Authenticates a Telegram mini app request: the signed initData from the
 * X-Telegram-Init-Data header is validated against the bot token, its
 * Telegram user is mapped to an approved bot chat, and that chat's user and
 * active project become the request context. No cookie or session.
 */
class AuthenticateMiniApp
{
    public const HEADER = 'X-Telegram-Init-Data';

    public const MAX_AGE_SECONDS = 86400;

    public function __construct(private Nutgram $bot) {}

    public function handle(Request $request, Closure $next)
    {
        // Without a real token Nutgram signs with a public placeholder, which
        // would let anyone forge init data. Refuse rather than trust it.
        if (! config('nutgram.token')) {
            throw new HttpException(503, 'The bot token is not configured.');
        }

        $initData = (string) ($request->header(self::HEADER) ?: $request->input('initData', ''));

        if ($initData === '') {
            throw new HttpException(401, 'Open this page from inside Telegram.');
        }

        try {
            $data = $this->bot->validateWebAppData($initData);
        } catch (Throwable) {
            throw new HttpException(401, 'The Telegram signature is not valid.');
        }

        if (! $data->user || Carbon::instance($data->auth_date)->lt(now()->subSeconds(self::MAX_AGE_SECONDS))) {
            throw new HttpException(401, 'The Telegram session has expired. Reopen the app.');
        }

        $chat = BotChat::with('user', 'project')
            ->where('telegram_user_id', $data->user->id)
            ->orWhere('chat_id', $data->user->id)
            ->first();

        if (! $chat || ! $chat->isUsable()) {
            throw new HttpException(403, 'This Telegram account is not approved. Send /start to the bot and ask an admin to approve it.');
        }

        $project = $chat->activeProject();

        if (! $project) {
            throw new HttpException(403, 'You have no project yet. Ask an admin to grant you one.');
        }

        Auth::setUser($chat->user);
        app()->setLocale($chat->user->locale ?: config('app.locale'));
        Carbon::setLocale(app()->getLocale());
        app()->instance(Project::class, $project);
        app()->instance(BotChat::class, $chat);

        return $next($request);
    }
}
