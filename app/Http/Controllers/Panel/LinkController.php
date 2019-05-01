<?php

namespace App\Http\Controllers\Panel;

use App\Model\Link;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LinkController extends Controller
{
    public function all()
    {
        $data['links'] = Link::orderBy('id', 'desc')->paginate(30);
        $data['setting'] = \Setting::all();
        return view('links_all', $data);
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
                $slug = \Str::random($length);
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

        return redirect('panel/links/'. $link->id);
    }

    public function detail($id)
    {
        $link = Link::findOrFail($id);
        $views = $link->views;

        $data['link'] = $link;
        $data['system'] = $this->counted($views, 'system');
        $data['browser'] = $this->counted($views, 'browser');
        $data['platform'] = $this->counted($views, 'platform');
        $data['device'] = $this->counted($views, 'device');

        $data['setting'] = \Setting::all();

        return view('links_detail', $data) ;
    }

    private function counted($views, $filter)
    {
        $grouped = $views->groupBy($filter);

        return $grouped->map(function ($item, $key) {
            return collect($item)->count();
        });
    }
}
