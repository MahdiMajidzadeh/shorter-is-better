<?php

namespace App\Http\Controllers\Panel;

use App\Model\Link;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function root()
    {
        $links = Link::all();

        $data['links_all'] = $links->count();
        $data['views'] = $links->sum('view');
        $data['last_links'] = Link::orderBy('id', 'desc')->take(5)->get();

        return view('dashboard', $data);
    }
}
