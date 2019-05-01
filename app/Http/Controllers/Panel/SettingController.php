<?php

namespace App\Http\Controllers\Panel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    public  function  root()
    {
        $data['minCharacter'] = \Setting::get('minCharacter', '3');
        $data['maxCharacter'] = \Setting::get('maxCharacter', '6');
        $data['defaultURL'] = \Setting::get('defaultURL', 'http://mahdi.majidzadeh.ir');
        $data['domain'] = \Setting::get('domain', request()->getSchemeAndHttpHost());

        return view('setting', $data);
    }

    public function  rootSubmit(Request $request)
    {
        \Setting::set('minCharacter', $request->get('minCharacter'));
        \Setting::set('maxCharacter', $request->get('maxCharacter'));
        \Setting::set('defaultURL', $request->get('defaultURL'));
        \Setting::set('domain', $request->get('domain'));
        \Setting::save();

        return redirect('panel/setting')->with('msg-ok','');
    }
}
