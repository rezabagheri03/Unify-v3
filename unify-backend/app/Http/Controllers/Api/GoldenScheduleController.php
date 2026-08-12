<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseSpecification;
use App\Models\Enrollment;
use App\Models\GoldenScheduleCache;
use App\Models\StudentPassedCourse;
use App\Models\Semester;
use App\Services\GoldenSchedulerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoldenScheduleController extends Controller
{
    public function generate(Request $request)
    {
        $user = $request->user();
        $semester = Semester::where('is_current', true)->first();
        if (! $semester) {
            return response()->json(['message' => 'نیم‌سال جاری تعریف نشده', 'code' => 'NO_CURRENT_SEMESTER'], 404);
        }

        // Remaining courses: not passed + not already enrolled
        $passed = StudentPassedCourse::where('student_id', $user->id)
            ->where('passed', true)->pluck('course_id');
        $enrolled = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['temporary', 'finalized'])
            ->where('semester_id', $semester->id)
            ->pluck('specification_id');

        $available = CourseSpecification::where('semester_id', $semester->id)
            ->where('is_active', true)
            ->whereNotIn('course_id', $passed)
            ->whereNotIn('id', $enrolled)
            ->with('course')
            ->get();

        $preferences = [
            'preferFreeDays' => $request->input('preferFreeDays'),
            'maxGap' => $request->input('maxGap'),
            'preferProfessors' => $request->input('preferProfessors', []),
        ];

        // Cache lookup (1h TTL). PERF-09: the lookup is SHARED across students —
        // the id-set is a function of passed/enrolled courses and prefs, so two
        // students with identical inputs legitimately share one generation.
        $hash = md5(json_encode(['ids' => $available->pluck('id')->sort()->values(), 'prefs' => $preferences]));
        $cached = GoldenScheduleCache::where('semester_id', $semester->id)
            ->where('preferences_hash', $hash)
            ->where('expires_at', '>', now())
            ->first();

        if ($cached) {
            return response()->json(['suggestions' => $cached->combos, 'cached' => true]);
        }

        $service = new GoldenSchedulerService();
        $suggestions = $service->generate($available->pluck('id')->all(), $preferences);

        GoldenScheduleCache::create([
            'id' => (string) Str::uuid(),
            'student_id' => $user->id,
            'semester_id' => $semester->id,
            'preferences_hash' => $hash,
            'combos' => $suggestions,
            'generated_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        return response()->json(['suggestions' => $suggestions, 'cached' => false]);
    }
}
