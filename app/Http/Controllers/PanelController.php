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
            ->select(DB::raw('Date(visited_at) as date'), DB::raw('count(*) as views'))
            ->where('visited_at', '>', now()->subDays(30))
            ->groupBy(DB::raw('Date(visited_at)'))
            ->get();

        $data['views'] = chart_data_line($views, 'date', 'views', 'views per day');

        return view('panel.dashboard', $data);
    }
}
