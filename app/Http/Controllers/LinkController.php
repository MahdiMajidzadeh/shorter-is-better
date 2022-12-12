<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
use AshAllenDesign\ShortURL\Models\ShortURL as ShortModel;

class LinkController extends Controller
{
    public function all(Request $request)
    {
        $data['links'] = ShortModel::orderBy('id', 'desc')->paginate(40);

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

        return redirect('links/' . $shorted['url_key']);
    }

    public function detail(Request $request, $short)
    {
        $data['short'] = ShortModel::findByKey($short);

        $data['operating_system'] = $this->getVisitData($data['short']->id, 'operating_system');
        $data['device_type']      = $this->getVisitData($data['short']->id, 'device_type');
        $data['browser']          = $this->getVisitData($data['short']->id, 'browser');

        $data['views'] = ShortURLVisit::query()
            ->select(DB::raw('Date(visited_at) as x'), DB::raw('count(*) as y'))
            ->where('short_url_id', $data['short']->id)
            ->where('visited_at', '>', now()->subDays(30))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        return view('panel.links-detail', $data);
    }

    public function deleteSubmit(Request $request, $id)
    {
        ShortURLVisit::where('short_url_id', $id)->delete();
        ShortModel::find($id)->delete();

        return redirect('links');
    }

    public function bulk(Request $request) : View
    {
        return view('panel.links-bulk');
    }

    public function bulkSubmit(Request $request)
    {
        $text = $request->get('text');
        
    }

    private function getVisitData($id, $type) : Collection
    {
        return ShortURLVisit::query()
            ->where('short_url_id', $id)
            ->groupBy($type)
            ->selectRaw($type . ' as name, count(*) as total')
            ->orderBy('total', 'desc')
            ->get();
    }
}
