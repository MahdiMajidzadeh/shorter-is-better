<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use AshAllenDesign\ShortURL\Classes\Builder;
use AshAllenDesign\ShortURL\Facades\ShortURL;

class LinkController extends Controller
{
    public function create(Request $request)
    {
//        setting()->set('base_url','https://mjdz.ir');
//
//        $builder = new Builder();
//        $shortURLObject = ShortURL::destinationUrl('https://destination.com')->make();
//        $shortURLObject = $builder->destinationUrl('https://destination.com')->urlKey('custom-key')->make();
//        $shortURLObject = $builder->destinationUrl('https://destination.com')->singleUse()->make();
//
//        $shortURL = $shortURLObject->default_short_url;

//        dd($shortURL);

        return view('panel.links-create');
    }

    public function createSubmit(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);
//        dd($request);

        $builder = new Builder();
        $shortURLObject = ShortURL::destinationUrl($request->get('url'));

        if ($request->filled('key')){
            $shortURLObject->urlKey($request->get('key'));
        }

        try {
            $shorted = $shortURLObject->make();
        }
        catch(Exception $e) {
            return redirect()->back()->with('msg-error', $e->getMessage());
        }

//        $shortURLObject = $builder->destinationUrl('https://destination.com')->singleUse()->make();
//        $shortURL = $shortURLObject->default_short_url;

        return redirect('links/'. $shorted['url_key']);
    }
}
