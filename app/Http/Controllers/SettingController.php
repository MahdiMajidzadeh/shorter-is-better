<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use DefStudio\Telegraph\Models\TelegraphBot;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $data['bot'] = TelegraphBot::query()->first();

        return view('panel.setting-all', $data);
    }

    public function botsCreate(Request $request): View
    {
        return view('panel.setting-bots-create');
    }

    public function botsCreateSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'token' => 'required|string',
        ]);

        $bot = TelegraphBot::create([
            'token' => $request->get('token'),
            'name'  => $request->get('name'),
        ]);

        $bot->registerWebhook()->send();
        $bot->registerCommands(bot_commands())->send();

        return redirect('settings')->with('msg-ok', __('Bot registered.'));
    }
}
