<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;

class PanelController extends Controller
{
    public function index(Request $request)
    {
        $data['views'] = ShortURLVisit::query()
            ->select(DB::raw('Date(visited_at) as date'), DB::raw('count(*) as Views'))
            ->where('visited_at','>', now()->subDays(7))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->toSql();

        return view('panel.dashboard',$data);
    }
}
