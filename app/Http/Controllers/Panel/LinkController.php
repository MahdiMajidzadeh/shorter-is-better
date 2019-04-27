<?php

namespace App\Http\Controllers\Panel;

use App\Model\Link;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LinkController extends Controller
{
    public function all()
    {
        return view('links_all');
    }

    public function create()
    {
        return view('links_create');
    }

    public function createSubmit(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'slug' => 'unique:links|nullable',
        ]);

        $link = new Link();
        $link->link = $request->get('url');

        if($request->filled('slug')){
            $link->slug = $request->slug;
            //TODO: check slug uniqueness
        }
        else{
            $slug = '';
            $length = \Setting::get('minCharacter');
            //TODO: check valid length
            while(true){
                $slug = Str::random($length);
                $links = Link::where('slug', $slug)->first();
                if(! $links){
                    break;
                };
            }
            $link->slug = $slug;
        }

        $link->user_id = 1;  //TODO: auth
        $link->view = 0;
        $link->save();

        return redirect('panel/links');
    }
}
