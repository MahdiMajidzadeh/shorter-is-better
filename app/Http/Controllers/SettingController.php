<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use DefStudio\Telegraph\Models\TelegraphBot;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        return view('panel.setting-all');
    }

    public function indexSubmit(Request $request): RedirectResponse
    {
        setting([
            'channel.has'      => $request->get('channel_has'),
            'channel.username' => $request->get('channel_username'),
        ])->save();

        return redirect('settings');
    }

    public function bots(Request $request)
    {
        $data['bots'] = TelegraphBot::all();

        return view('panel.setting-all', $data);
    }

    public function botsCreate(Request $request)
    {
        return view('panel.setting-bots-create');
    }

    public function botsCreateSubmit(Request $request)
    {
        $bot = TelegraphBot::create([
            'token' => $request->get('token'),
            'name'  => $request->get('name'),
        ]);

        $bot->registerWebhook()->send();
        $bot->registerCommands(bot_commands())->send();

        return redirect()->back();
    }
}
