<?php

namespace App\Http\Controllers;

use App\Model\Link;
use App\Model\View;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class RedirectController extends Controller
{
    public function root()
    {
        $baseURL = \Setting::get('defaultURL', 'http://mahdi.majidzadeh.ir');

        return redirect($baseURL);
    }

    public function  link(Request $request, $slug)
    {
        $link = Link::where('slug', $slug)->first();

        if(!$link){
            $baseURL = \Setting::get('defaultURL', 'http://mahdi.majidzadeh.ir');
            return redirect($baseURL);
        }

        $agent = new Agent();

        $system = 'Unknown';
        if($agent->isDesktop()){
            $system = 'Desktop';
        } elseif ($agent->isPhone()){
            $system = 'Phone';
        }
        elseif ($agent->isTablet()){
            $system = 'Tablet';
        }

        $view = new View();

        $view->link_id = $link->id;
        $view->browser = $agent->browser();
        $view->platform = $agent->platform();
        $view->device = $agent->device();
        $view->system = $system;
        $view->country = \Location::get()->countryName ?: "Unknown";
        $view->city = \Location::get()->cityName ?: "Unknown";
        $view->ip = $request->ip();

        $view->save();

        $link->increment('view');

        return redirect($link->link);

    }
}
