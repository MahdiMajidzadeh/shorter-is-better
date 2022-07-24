<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use AshAllenDesign\ShortURL\Models\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;

class LinkController extends Controller
{
    public function all(Request $request)
    {
        $data['links'] = \AshAllenDesign\ShortURL\Models\ShortURL::orderBy('id', 'desc')->paginate(40);

        return view('panel.links-all', $data);
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

        return redirect('links/'.$shorted['url_key']);
    }

    public function detail(Request $request, $short)
    {
        $data['short'] = ShortURL::findByKey($short);
        $data['browser'] = ShortURLVisit::where('short_url_id', $data['short']->id)
            ->groupBy('browser')
            ->selectRaw('browser as name, count(*) as total')
            ->orderBy('total', 'desc')
            ->get();
        $data['device_type'] = ShortURLVisit::where('short_url_id', $data['short']->id)
            ->groupBy('device_type')
            ->selectRaw('device_type as name, count(*) as total')
            ->orderBy('total', 'desc')
            ->get();
        $data['operating_system'] = ShortURLVisit::where('short_url_id', $data['short']->id)
            ->groupBy('operating_system')
            ->selectRaw('operating_system as name, count(*) as total')
            ->orderBy('total', 'desc')
            ->get();

        return view('panel.links-detail', $data);
    }

    public function deleteSubmit(Request $request, $id)
    {
        ShortURLVisit::where('short_url_id', $id)->delete();
        ShortURL::find($id)->delete();

        return redirect('links');
    }
}
