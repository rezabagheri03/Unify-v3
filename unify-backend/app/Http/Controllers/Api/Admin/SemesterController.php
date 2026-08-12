<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Enrollment;
use App\Models\CourseSpecification;
use App\Models\Notification;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        \App\Models\AuditLog::record($request->user()->id, 'semester_created', 'semester', $request->id ?? null, $request);
        return response()->json(['message' => 'نیم‌سال جدید ایجاد شد']);
    }

    /**
     * Semester state machine (BE audit fix — the machine existed on paper but
     * nothing ever wrote global_state ACTIVE, leaving the grace guards, the
     * wipe cron and the whole enrollment-closing flow dead).
     *
     * enrolling -> active: registration closes for NEW adds immediately;
     * students with a temporary list get 24h to finalize it (grace).
     */
    public function activateCurrent(Request $request)
    {
        $semester = Semester::where('is_current', true)->first();
        if (! $semester) {
            return response()->json(['message' => 'نیم‌سال جاری تعریف نشده است', 'code' => 'NO_CURRENT_SEMESTER'], 404);
        }
        if ($semester->global_state !== 'enrolling') {
            return response()->json(['message' => 'نیم‌سال قبلاً فعال شده است', 'code' => 'ALREADY_ACTIVE'], 400);
        }

        $semester->update([
            'global_state' => 'active',
            'grace_period_ends_at' => now()->addHours((int) config('unify.grace_period_hours', 24)),
            'grace_period_handled' => false,
        ]);

        // Fan-out: registration_close notification to every active student.
        // Chunked inserts; the 5s per-user poll-cache TTL self-heals stale reads.
        $students = \App\Models\User::where('role', 'student')->where('is_banned', false)->pluck('id');
        $now = now();
        foreach ($students->chunk(200) as $chunk) {
            $rows = [];
            foreach ($chunk as $sid) {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $sid,
                    'type' => 'registration_close',
                    'title' => 'ثبت‌نام بسته شد',
                    'body' => 'فاز انتخاب واحد بسته شد؛ تا ۲۴ ساعت آینده فقط می‌توانید لیست موقت خود را نهایی کنید.',
                    'priority' => 'high',
                    'read' => false,
                    'created_at' => $now,
                ];
            }
            Notification::insert($rows);
        }

        \App\Models\AuditLog::record($request->user()->id, 'semester_activated', 'semester', $semester->id, $request, [
            'notified_students' => $students->count(),
        ]);
        return response()->json([
            'message' => 'نیم‌سال فعال شد؛ مهلت ۲۴ ساعته نهایی‌سازی آغاز گشت',
            'grace_period_ends_at' => $semester->grace_period_ends_at,
        ]);
    }

    private function graceActive(Semester $semester): bool
    {
        return $semester->global_state === 'active'
            && $semester->grace_period_ends_at
            && now()->lessThan($semester->grace_period_ends_at)
            && ! $semester->grace_period_handled;
    }
}
