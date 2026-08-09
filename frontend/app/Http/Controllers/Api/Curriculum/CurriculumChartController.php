<?php

namespace App\Http\Controllers\Api\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\CurriculumChart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CurriculumChartController extends Controller
{
    public function index(Request $request)
    {
        $charts = CurriculumChart::where('status', 'approved')
            ->where('department_id', $request->user()->department_id)
            ->get();

        return response()->json($charts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'entry_year' => 'required|integer',
            'chart_data' => 'required|array',
        ]);

        $chart = CurriculumChart::create([
            'id' => Str::uuid(),
            'department_id' => $request->user()->department_id,
            'entry_year' => $request->entry_year,
            'chart_data' => $request->chart_data,
            'status' => 'draft',
        ]);

        return response()->json($chart, 201);
    }

    public function submitForApproval($id)
    {
        $chart = CurriculumChart::findOrFail($id);
        $chart->update(['status' => 'pending_approval']);

        return response()->json(['message' => 'ارسال برای تأیید']);
    }
}