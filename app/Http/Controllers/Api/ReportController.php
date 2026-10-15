<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function summary(Request $request, ReportService $reports): JsonResponse
    {
        $request->validate(['days' => 'nullable|integer|min:1|max:365']);

        return response()->json($reports->summary($this->project(), (int) $request->get('days', 7)));
    }
}
