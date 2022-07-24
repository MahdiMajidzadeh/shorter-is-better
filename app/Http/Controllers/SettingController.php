<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DefStudio\Telegraph\Models\TelegraphBot;

class SettingController extends Controller
{
    public function bots(Request $request)
    {
        $data['bots'] = TelegraphBot::all();

        return view('panel.setting-bots-all', $data);
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

        $bot->registerCommands([
            'short'    => 'short url',
            'shortKey' => 'short url with custom key',
            'stat'     => 'show stat for shorted url',
            'auth'     => 'Authenticate Bot with your account',
            'report'   => 'Short Report of Last 7 days',
        ])->send();

        return redirect()->back();
    }
}
