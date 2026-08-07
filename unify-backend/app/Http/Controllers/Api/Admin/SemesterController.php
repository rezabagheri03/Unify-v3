<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Enrollment;
use App\Models\CourseSpecification;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SemesterController extends Controller
{
    public function current(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        if (! $semester) {
            return response()->json(['message' => 'نیم‌سال جاری تعریف نشده است', 'code' => 'NO_CURRENT_SEMESTER'], 404);
        }
        $semester->shamsi_start = ShamsiService::toShamsi($semester->start_date_g);
        $semester->shamsi_end = ShamsiService::toShamsi($semester->end_date_g);
        $semester->grace_active = $this->graceActive($semester);
        return response()->json($semester);
    }

    public function past(Request $request)
    {
        $semesters = Semester::where('is_current', false)
            ->orderByDesc('start_date_g')
            ->get()
            ->map(function ($s) {
                $s->archived_enrollments = Enrollment::where('semester_id', $s->id)->where('status', 'archived')->count();
                return $s;
            });
        return response()->json($semesters);
    }

    public function createNewSemester(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'start_shamsi' => 'required|string',
            'end_shamsi' => 'required|string',
        ]);

        $old = Semester::where('is_current', true)->first();

        // Grace active -> cannot start a new semester (F14)
        if ($old && $this->graceActive($old)) {
            return response()->json([
                'message' => 'مهلت ۲۴ ساعته فعال است - نمی‌توان ترم جدید تعریف کرد',
                'code' => 'GRACE_ACTIVE',
            ], 403);
        }

        DB::transaction(function () use ($request, $old) {
            if ($old) {
                // 1) Capture old state FIRST (fix: archive/deactivate must target the old semester)
                Enrollment::where('semester_id', $old->id)
                    ->where('status', 'finalized')
                    ->update(['status' => 'archived']);

                CourseSpecification::where('semester_id', $old->id)
                    ->update(['is_active' => false]);

                $old->update(['is_current' => false]);
            }

            Semester::create([
                'id' => $request->name,
                'name' => $request->name,
                'is_current' => true,
                'global_state' => 'enrolling',
                'start_date_g' => ShamsiService::toGregorian($request->start_shamsi),
                'end_date_g' => ShamsiService::toGregorian($request->end_shamsi),
                'shamsi_original_start' => $request->start_shamsi,
                'shamsi_original_end' => $request->end_shamsi,
            ]);
        });

        return response()->json(['message' => 'نیم‌سال جدید ایجاد شد']);
    }

    private function graceActive(Semester $semester): bool
    {
        return $semester->global_state === 'active'
            && $semester->grace_period_ends_at
            && now()->lessThan($semester->grace_period_ends_at)
            && ! $semester->grace_period_handled;
    }
}
