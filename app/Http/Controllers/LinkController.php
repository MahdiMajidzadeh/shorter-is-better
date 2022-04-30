<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AshAllenDesign\ShortURL\Classes\Builder;
use AshAllenDesign\ShortURL\Facades\ShortURL;

class LinkController extends Controller
{
    public function create(Request $request)
    {
        setting()->set('base_url','https://mjdz.ir');

        $builder = new Builder();
        $shortURLObject = ShortURL::destinationUrl('https://destination.com')->make();
        $shortURLObject = $builder->destinationUrl('https://destination.com')->urlKey('custom-key')->make();
        $shortURLObject = $builder->destinationUrl('https://destination.com')->singleUse()->make();

        $shortURL = $shortURLObject->default_short_url;

        dd($shortURL);
    }
}
