<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use AshAllenDesign\ShortURL\Facades\ShortURL;

class LinkController extends Controller
{
    public function all(Request $request)
    {
        $data['links'] = \AshAllenDesign\ShortURL\Models\ShortURL::orderBy('id', 'desc')->paginate(1);

        return view('panel.links-all',$data);
    }

    public function create(Request $request)
    {
        return view('panel.links-create');
    }

    public function createSubmit(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $shortURLObject = ShortURL::destinationUrl($request->get('url'));

        if ($request->filled('key')) {
            $shortURLObject->urlKey($request->get('key'));
        }

        try {
            $shorted = $shortURLObject->make();
        } catch (Exception $e) {
            return redirect()->back()->with('msg-error', $e->getMessage());
        }

//        $shortURLObject = $builder->destinationUrl('https://destination.com')->singleUse()->make();
//        $shortURL = $shortURLObject->default_short_url;

        return redirect('links/' . $shorted['url_key']);
    }

    public function detail(Request $request, $short)
    {
        $data['short'] = \AshAllenDesign\ShortURL\Models\ShortURL::findByKey($short);

        return view('panel.links-detail', $data);
    }
}
