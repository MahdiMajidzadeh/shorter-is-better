<?php

namespace App\Http\Controllers;

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
}
