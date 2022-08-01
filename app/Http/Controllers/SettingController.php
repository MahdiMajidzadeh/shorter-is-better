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
            'shortkey'   => 'short url with custom key',
            'forchannel' => 'make short for channel',
            'report'     => 'short report of last 7 days',
            'short'      => 'short url',
            'stat'       => 'show stat for shorted url',
            'bulk'       => 'short bulk of links in text',
            'auth'       => 'authenticate bot with your account',
        ])->send();

        return redirect()->back();
    }
}
