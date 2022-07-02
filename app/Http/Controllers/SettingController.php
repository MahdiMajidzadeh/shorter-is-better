<?php

namespace App\Http\Controllers;

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function tokens(Request $request)
    {
        $data['tokens'] = auth()->user()->tokens;

        return view('panel.setting-tokens-all', $data);
    }

    public function tokensCreateSubmit(Request $request)
    {
        $token = auth()->user()->createToken(now()->format('Y-m-d H:i:s'));

        return redirect()->back()->with('token', $token->plainTextToken);
    }

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
            'shortkey' => 'short url with custom key',
            'stat'     => 'show stat for shorted url',
            'auth'     => 'Authenticate Bot with your account',
        ])->send();

        return redirect()->back();
    }
}
