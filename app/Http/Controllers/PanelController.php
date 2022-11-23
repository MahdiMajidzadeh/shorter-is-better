<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;

class PanelController extends Controller
{
    public function index(Request $request)
    {
        $views = ShortURLVisit::query()
            ->select(DB::raw('Date(visited_at) as x'), DB::raw('count(*) as y'))
            ->where('visited_at', '>', now()->subDays(30))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $data['views'] = $views;

        return view('panel.dashboard', $data);
    }
}
