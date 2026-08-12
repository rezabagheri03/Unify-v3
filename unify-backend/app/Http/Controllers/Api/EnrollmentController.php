<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseSpecification;
use App\Models\Enrollment;
use App\Models\IdempotencyKeys;
use App\Models\AcademicStatusHistory;
use App\Models\StudentPassedCourse;
use App\Models\Semester;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentController extends Controller
{
    /** Max credits per declared honor status (F02 + F03). */
    private function getMaxCredits(?string $status): int
    {
        return match ($status) {
            'final_semester' => 24,
            'gpa_a' => 24,
            'conditional' => 14,
            default => 20,
        };
    }

    public function indexTemp(Request $request)
    {
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->where('status', 'temporary')
            ->with(['specification.course', 'specification.professor:id,first_name,last_name'])
            ->get();

        return response()->json($enrollments);
    }

    public function storeTemp(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'specification_id' => 'required|exists:course_specifications,id',
        ]);
        $specId = $request->specification_id;

        // Idempotency (FIX H1, hardened): keys are namespaced PER USER and the
        // first response is replayed verbatim (body + status) on retry.
        // Post-audit F-07: bound the header before it can reach the varchar(36)
        // column and 500 on oversized input.
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey !== null && (! is_string($idempotencyKey) || strlen($idempotencyKey) > 36)) {
            return response()->json(['message' => 'کلید idempotency نامعتبر است', 'code' => 'BAD_IDEMPOTENCY_KEY'], 422);
        }
        if ($idempotencyKey) {
            $existing = IdempotencyKeys::where('key', $idempotencyKey)
                ->where('user_id', $user->id)
                ->first();
            if ($existing && $existing->response_body) {
                return response()->json(json_decode($existing->response_body, true), $existing->response_code);
            }
        }

        $spec = CourseSpecification::with('course')->findOrFail($specId);
        $semester = Semester::where('is_current', true)->first();

        if (! $semester || $spec->semester_id !== $semester->id || ! $spec->is_active) {
            return response()->json(['message' => 'این درس برای نیم‌سال جاری فعال نیست', 'code' => 'SPEC_NOT_ACTIVE'], 422);
        }

        // Honor status must be declared first
        if (! $user->academic_status_declared) {
            return response()->json(['message' => 'ابتدا وضعیت تحصیلی خود را اعلام کنید', 'code' => 'HONOR_NOT_DECLARED'], 400);
        }

        // Grace period: new temp adds blocked while grace active
        if ($semester->global_state === 'active' && $semester->grace_period_ends_at && now()->lessThan($semester->grace_period_ends_at) && ! $semester->grace_period_handled) {
            return response()->json([
                'message' => 'فاز ثبت‌نام بسته شده - فقط نهایی‌سازی لیست موجود ممکن است',
                'code' => 'ENROLLING_CLOSED_GRACE',
            ], 403);
        }

        // Race + duplication fix (BE audit): duplicate / credit / overlap checks
        // and the insert run inside ONE transaction with the student's current
        // rows locked, and all checks read a single snapshot (PERF-08: no more
        // triple re-fetch of the same data).
        $result = DB::transaction(function () use ($user, $spec, $semester, $specId) {
            $rows = Enrollment::where('student_id', $user->id)
                ->where('semester_id', $semester->id)
                ->whereIn('status', ['temporary', 'finalized'])
                ->with(['specification.course'])
                ->lockForUpdate()
                ->get();

            // Duplicate guard now covers FINALIZED too — re-adding a course
            // after finalization used to hit the unique index and 500.
            if ($rows->contains(fn ($e) => $e->specification_id === $specId)) {
                return ['code' => 409, 'body' => ['message' => 'این درس قبلاً در لیست شماست', 'code' => 'DUPLICATE']];
            }

            // TODO-041 (product decision): ONE section per (course, professor)
            // per term. Retaking the same course+prof in a LATER term is
            // allowed (fail/improve), so the guard stays scoped to the
            // current-semester snapshot already locked above — no extra query.
            if ($rows->contains(fn ($e) =>
                $e->specification?->course_id === $spec->course_id
                && $e->specification?->professor_id === $spec->professor_id
            )) {
                return ['code' => 409, 'body' => [
                    'message' => 'این درس را با این استاد در این نیم‌سال قبلاً اخذ کرده‌اید',
                    'code' => 'COURSE_PROF_DUPLICATE',
                ]];
            }

            // Credit cap counts temp AND finalized selections in this semester.
            $currentCredits = $rows->sum(fn ($e) => $e->specification?->course?->credits ?? 0);
            $maxCredits = $this->getMaxCredits($user->academic_status_declared);
            if ($currentCredits + $spec->course->credits > $maxCredits) {
                return ['code' => 400, 'body' => [
                    'message' => "سقف واحد برای وضعیت شما {$maxCredits} واحد است",
                    'code' => 'CREDIT_LIMIT_EXCEEDED',
                ]];
            }

            // Time overlap (same day_of_week + interval overlap, overnight-aware) — final_semester ignores
            if ($user->academic_status_declared !== 'final_semester') {
                foreach ($rows as $enr) {
                    $old = $enr->specification;
                    if (! $old) {
                        continue;
                    }
                    if ($this->specsOverlap($old, $spec)) {
                        return ['code' => 409, 'body' => [
                            'message' => 'تداخل زمانی با ' . $old->course->name . ' ' . $old->day_of_week,
                            'code' => 'TIME_OVERLAP',
                            'errors' => ['conflicting_specs' => [$old->id]],
                        ]];
                    }
                }

                if ($spec->exam_date_final_g) {
                    foreach ($rows as $enr) {
                        $oldExam = $enr->specification?->exam_date_final_g;
                        if (! $oldExam) {
                            continue;
                        }
                        $newExam = $spec->exam_date_final_g;
                        if ($oldExam->toDateString() === $newExam->toDateString() && abs($oldExam->diffInMinutes($newExam)) < 120) {
                            return ['code' => 409, 'body' => [
                                'message' => 'تداخل امتحان نهایی با ' . $enr->specification->course->name . ' - هر دو ' . $newExam->format('Y/m/d'),
                                'code' => 'EXAM_OVERLAP',
                            ]];
                        }
                    }
                }
            }

            $enrollment = Enrollment::create([
                'id' => Str::uuid(),
                'student_id' => $user->id,
                'specification_id' => $specId,
                'semester_id' => $spec->semester_id,
                'status' => 'temporary',
                'academic_status_at_enrollment' => $user->academic_status_declared,
                'enrolled_at' => now(),
            ]);

            // Prereq warnings (warn, never block — honor system)
            $warnings = $this->prereqWarnings($user->id, $spec);
            $warnings = array_merge($warnings, $this->coreqWarnings($user->id, $spec, $rows));

            $enrollment->load(['specification.course', 'specification.professor:id,first_name,last_name']);
            $enrollment->shamsi_enrolled = ShamsiService::toShamsi($enrollment->enrolled_at);

            return ['code' => 201, 'body' => [
                'message' => 'به لیست موقت اضافه شد',
                'enrollment' => $enrollment,
                'warnings' => $warnings,
            ]];
        });

        if ($result['code'] === 201 && $idempotencyKey) {
            // Post-audit: the key column is globally UNIQUE — a same-key race
            // (two parallel first-attempts) must not turn into a 500; the
            // winner's response is replayed instead.
            try {
                IdempotencyKeys::create([
                    'id' => Str::uuid(),
                    'key' => $idempotencyKey,
                    'user_id' => $user->id,
                    'response_code' => 201,
                    // Full response (not just the message) so a retried client sees
                    // exactly what the first attempt produced, including warnings.
                    'response_body' => json_encode($result['body'], JSON_UNESCAPED_UNICODE),
                    'expires_at' => now()->addHours((int) config('unify.grace_period_hours', 24)),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $existing = IdempotencyKeys::where('key', $idempotencyKey)
                    ->where('user_id', $user->id)->first();
                if ($existing && $existing->response_body) {
                    return response()->json(json_decode($existing->response_body, true), $existing->response_code);
                }
            }
        }

        return response()->json($result['body'], $result['code']);
    }

    public function removeTemp(Request $request, $id)
    {
        $enrollment = Enrollment::where('id', $id)
            ->where('student_id', $request->user()->id)
            ->where('status', 'temporary')
            ->firstOrFail();

        $enrollment->delete();
        return response()->json(['message' => 'از لیست موقت حذف شد']);
    }

    public function finalize(Request $request)
    {
        $user = $request->user();

        // Lazy grace check fallback (FIX C3)
        $semester = Semester::where('is_current', true)->first();
        if ($semester && $semester->grace_period_ends_at && now()->greaterThan($semester->grace_period_ends_at) && ! $semester->grace_period_handled) {
            \Artisan::call('enrollments:wipe-grace');
            return response()->json(['message' => 'دوره grace تمام شده است', 'code' => 'GRACE_ENDED'], 403);
        }

        if (! $user->academic_status_declared) {
            return response()->json(['message' => 'وضعیت تحصیلی خود را اعلام کنید', 'code' => 'HONOR_NOT_DECLARED'], 400);
        }

        $tempEnrollments = Enrollment::where('student_id', $user->id)
            ->where('status', 'temporary')
            ->with('specification.course')
            ->get();

        if ($tempEnrollments->isEmpty()) {
            return response()->json(['message' => 'لیست موقت شما خالی است', 'code' => 'EMPTY_TEMP'], 400);
        }

        $result = DB::transaction(function () use ($tempEnrollments, $user, $semester) {
            // Post-audit F-02: revalidate against the CURRENT declared status.
            // Status is freely re-declarable and storeTemp checks used the status
            // at add time, so a declare-high → add → declare-low → finalize
            // sequence could stamp 24 units onto a 14-unit status. Re-run the
            // credit cap and overlap rules now, under the same locked snapshot.
            // Cap/overlaps must count FINALIZED rows too — temps alone are not
            // the student's full load for the semester.
            $semesterId = $semester?->id ?? $tempEnrollments->first()->semester_id;
            $allRows = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['temporary', 'finalized'])
                ->where('semester_id', $semesterId)
                ->with('specification.course')
                ->lockForUpdate()
                ->get();

            $status = $user->academic_status_declared;
            $totalCredits = $allRows->sum(fn ($e) => $e->specification?->course?->credits ?? 0);
            $maxCredits = $this->getMaxCredits($status);
            if ($totalCredits > $maxCredits) {
                return ['code' => 422, 'body' => [
                    'message' => "سقف واحد برای وضعیت فعلی شما {$maxCredits} واحد است — موارد اضافی را از لیست موقت حذف کنید",
                    'code' => 'CREDIT_LIMIT_EXCEEDED',
                ]];
            }

            if ($status !== 'final_semester') {
                $list = $allRows->values();
                for ($i = 0; $i < $list->count(); $i++) {
                    for ($j = $i + 1; $j < $list->count(); $j++) {
                        $a = $list[$i]->specification;
                        $b = $list[$j]->specification;
                        if (! $a || ! $b) {
                            continue;
                        }
                        if ($this->specsOverlap($a, $b)) {
                            return ['code' => 422, 'body' => [
                                'message' => 'تداخل زمانی: ' . ($a->course->name ?? $a->id) . ' با ' . ($b->course->name ?? $b->id),
                                'code' => 'TIME_OVERLAP',
                            ]];
                        }
                        if ($a->exam_date_final_g && $b->exam_date_final_g
                            && $a->exam_date_final_g->toDateString() === $b->exam_date_final_g->toDateString()
                            && abs($a->exam_date_final_g->diffInMinutes($b->exam_date_final_g)) < 120) {
                            return ['code' => 422, 'body' => [
                                'message' => 'تداخل امتحان نهایی: ' . ($a->course->name ?? $a->id) . ' با ' . ($b->course->name ?? $b->id),
                                'code' => 'EXAM_OVERLAP',
                            ]];
                        }
                    }
                }
            }

            $finalized = 0;
            foreach ($tempEnrollments as $enr) {
                $enr->update([
                    'status' => 'finalized',
                    'finalized_at' => now(),
                    'academic_status_at_enrollment' => $user->academic_status_declared,
                ]);
                $finalized++;

                if ($user->academic_status_declared === 'final_semester') {
                    AcademicStatusHistory::updateOrCreate(
                        [
                            'student_id' => $user->id,
                            'semester_id' => $enr->semester_id,
                            'status' => 'final_semester',
                        ],
                        ['declared_at' => now(), 'id' => (string) Str::uuid()]
                    );
                }
            }
            return ['code' => 200, 'body' => ['message' => "انتخاب واحد نهایی شد ({$finalized} درس)"]];
        });

        return response()->json($result['body'], $result['code']);
    }

    public function myEnrollments(Request $request)
    {
        $user = $request->user();
        $current = Semester::where('is_current', true)->value('id');

        $query = Enrollment::where('student_id', $user->id)
            ->with(['specification.course', 'specification.professor:id,first_name,last_name', 'semester']);

        if ($request->boolean('archived')) {
            $query->where('status', 'archived');
        } else {
            $query->whereIn('status', ['temporary', 'finalized'])->where('semester_id', $current);
        }

        return response()->json($query->orderBy('enrolled_at')->get());
    }

    // ---- helpers ----

    private function timesOverlap(CourseSpecification $a, CourseSpecification $b): bool
    {
        // Normalize overnight (is_next_day) to 24:00-based ranges.
        $aEnd = $a->is_next_day ? '24:00' : $a->time_end;
        $bEnd = $b->is_next_day ? '24:00' : $b->time_end;
        return max($a->time_start, $b->time_start) < min($aEnd, $bEnd);
    }

    /**
     * Same-day OR cross-midnight adjacency overlap (post-audit fix): a class
     * ending 01:00 on day D+1 collides with a 00:30 class whose day_of_week IS
     * D+1 — the previous same-day-only check structurally missed that pair.
     */
    private const DAY_ORDER = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];

    private function specsOverlap(CourseSpecification $a, CourseSpecification $b): bool
    {
        if ($a->day_of_week === $b->day_of_week) {
            return $this->timesOverlap($a, $b);
        }

        // Cross-midnight: A spills into B's day iff B starts before A's tail end.
        if ($a->is_next_day && $this->nextDay($a->day_of_week) === $b->day_of_week) {
            return $b->time_start < $a->time_end;
        }
        if ($b->is_next_day && $this->nextDay($b->day_of_week) === $a->day_of_week) {
            return $a->time_start < $b->time_end;
        }
        return false;
    }

    private function nextDay(string $day): ?string
    {
        $idx = array_search($day, self::DAY_ORDER, true);
        return $idx === false ? null : self::DAY_ORDER[($idx + 1) % 7];
    }

    private function prereqWarnings(string $studentId, CourseSpecification $spec): array
    {
        // PERF-08 fix: one pluck + one whereIn instead of an EXISTS per prereq.
        $prereqs = DB::table('course_prerequisites')->where('course_id', $spec->course_id)->pluck('prerequisite_id');
        if ($prereqs->isEmpty()) {
            return [];
        }

        $passedIds = StudentPassedCourse::where('student_id', $studentId)
            ->whereIn('course_id', $prereqs)
            ->where('passed', true)
            ->pluck('course_id');

        $warnings = [];
        foreach ($prereqs->diff($passedIds) as $missingId) {
            $warnings[] = ['type' => 'prereq', 'course_id' => $missingId, 'message' => "پیش‌نیاز {$missingId} را پاس نکرده‌اید، ادامه می‌دهید؟"];
        }
        return $warnings;
    }

    private function coreqWarnings(string $studentId, CourseSpecification $spec, $currentRows): array
    {
        $coreqs = DB::table('course_corequisites')->where('course_id', $spec->course_id)->pluck('corequisite_id');
        if ($coreqs->isEmpty()) {
            return [];
        }

        // BE fix: corequisites are COURSE ids — compare against the courses in
        // the student's current selections. The old code compared them against
        // specification ids, so the check was structurally always false.
        $selectedCourseIds = $currentRows
            ->map(fn ($e) => $e->specification?->course_id)
            ->filter()
            ->unique()
            ->values();

        $warnings = [];
        foreach ($coreqs->diff($selectedCourseIds) as $missingId) {
            $warnings[] = ['type' => 'coreq', 'course_id' => $missingId, 'message' => "هم‌نیاز {$missingId} در لیست شما نیست"];
        }
        return $warnings;
    }
}
