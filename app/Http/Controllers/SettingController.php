<?php

namespace App\Http\Controllers;

use Throwable;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Support\BotRegistrar;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Bot settings. The token lives in the TELEGRAM_TOKEN environment variable;
 * this page shows whether it works and (re)registers the webhook and the
 * command list.
 */
class SettingController extends Controller
{
    public function index(Request $request, Nutgram $bot): View
    {
        $configured = (bool) config('nutgram.token');
        $me = null;
        $error = null;

        if ($configured) {
            try {
                $me = Cache::remember('bot.me', 300, fn () => $bot->getMe()?->username);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('panel.setting-all', [
            'configured'  => $configured,
            'botUsername' => $me,
            'error'       => $error,
            'webhookUrl'  => BotRegistrar::webhookUrl(),
            'legacyToken' => $this->legacyToken(),
        ]);
    }

    public function register(Request $request, BotRegistrar $registrar): RedirectResponse
    {
        if (! config('nutgram.token')) {
            return redirect('settings')->with('msg-error', __('Set TELEGRAM_TOKEN first.'));
        }

        try {
            $registrar->register();
        } catch (Throwable $e) {
            return redirect('settings')->with('msg-error', $e->getMessage());
        }

        Cache::forget('bot.me');

        return redirect('settings')->with('msg-ok', __('Webhook and commands registered.'));
    }

    /** The token of the bot created in the old Telegraph-based panel, until it is moved to env. */
    private function legacyToken(): ?string
    {
        if (config('nutgram.token') || ! Schema::hasTable('telegraph_bots')) {
            return null;
        }

        return DB::table('telegraph_bots')->orderBy('id')->value('token');
    }
}
