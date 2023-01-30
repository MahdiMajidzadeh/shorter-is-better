<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Laravel\Telescope\Contracts\PrunableRepository;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $data['bot'] = TelegraphBot::query()->first();
        $data['telescope_active'] = cache()->get('telescope:pause-recording');

        return view('panel.setting-all', $data);
    }

    public function homeSubmit(Request $request): RedirectResponse
    {
        setting([
            'home.title'        => $request->get('title'),
            'home.title-accent' => $request->get('title-accent'),
            'home.subtitle'     => $request->get('subtitle'),
            'home.cta-title'    => $request->get('cta-title'),
            'home.cta-url'      => $request->get('cta-url'),
        ])->save();

        return redirect('settings')->with('msg-ok', 'Home Settings Save Successfully');
    }

    public function channelSubmit(Request $request): RedirectResponse
    {
        $chat = TelegraphChat::where('chat_id', $request->get('channel_id'))->first();

        if (! $chat) {
            $chat = TelegraphBot::first()->chats()->create([
                'chat_id' => $request->get('channel_id'),
                'name'    => $request->get('channel_id'),
            ]);
        }

        setting([
            'channel.has'      => $request->get('channel_has'),
            'channel.username' => $request->get('channel_username'),
            'channel.id'       => $request->get('channel_id'),
        ])->save();

        bot_update();

        return redirect('settings')->with('msg-ok', 'Channel Settings Save Successfully');
    }

    public function botsCreate(Request $request): View
    {
        return view('panel.setting-bots-create');
    }

    public function botsCreateSubmit(Request $request): RedirectResponse
    {
        $bot = TelegraphBot::create([
            'token' => $request->get('token'),
            'name'  => $request->get('name'),
        ]);

        $bot->registerWebhook()->send();
        $bot->registerCommands(bot_commands())->send();

        return redirect('settings');
    }

    public function telescopeAction(Request $request, $action, PrunableRepository $repo): RedirectResponse
    {
        switch ($action) {
            case 'pause':
                if (! cache()->get('telescope:pause-recording')) {
                    cache()->put('telescope:pause-recording', true, now()->addDays(180));
                }

                return redirect('settings')->with('msg-ok', 'Telescoped Paused');

            case 'resume':
                if (cache()->get('telescope:pause-recording')) {
                    cache()->forget('telescope:pause-recording');
                }

                return redirect('settings')->with('msg-ok', 'Telescoped Resumed');
            case 'prune':
                $repo->prune(now()->addHours(48));

                return redirect('settings')->with('msg-ok', 'Telescoped Pruned');
            case 'prune-all':
                $repo->prune(now());

                return redirect('settings')->with('msg-ok', 'Telescoped Pruned All');
        }
    }
}
