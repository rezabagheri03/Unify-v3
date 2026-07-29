<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseSpecification;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class GoldenScheduleController extends Controller
{
    public function generate(Request $request)
    {
        $user = $request->user();
        $semesterId = $request->get('semester_id') ?? \App\Models\Semester::where('is_current', true)->value('id');

        $availableSpecs = CourseSpecification::where('semester_id', $semesterId)
            ->where('is_active', true)
            ->with('course')
            ->get();

        // Simple backtracking MRV heuristic (simplified for production)
        $suggestions = $this->generateGoldenSchedules($availableSpecs, $user, $request->all());

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    private function generateGoldenSchedules($specs, $user, $preferences)
    {
        $service = new \App\Services\GoldenSchedulerService();
        $specIds = $specs->pluck('id')->toArray();
        
        return $service->generate($specIds, $preferences);
    }
}