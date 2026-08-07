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
            ->with(['specification.course', 'specification.professor'])
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

        // Idempotency (FIX H1)
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey) {
            $existing = IdempotencyKeys::where('key', $idempotencyKey)->first();
            if ($existing) {
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

        // Duplicate guard
        $duplicate = Enrollment::where('student_id', $user->id)
            ->where('specification_id', $specId)
            ->where('status', 'temporary')
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'این درس قبلاً در لیست موقت شماست', 'code' => 'DUPLICATE'], 409);
        }

        $currentCredits = $this->currentCredits($user->id);
        $maxCredits = $this->getMaxCredits($user->academic_status_declared);
        if ($currentCredits + $spec->course->credits > $maxCredits) {
            return response()->json([
                'message' => "سقف واحد برای وضعیت شما {$maxCredits} واحد است",
                'code' => 'CREDIT_LIMIT_EXCEEDED',
            ], 400);
        }

        // Time overlap (same day_of_week + interval overlap, overnight-aware) — final_semester ignores
        if ($user->academic_status_declared !== 'final_semester') {
            $overlap = $this->findTimeOverlap($user->id, $spec);
            if ($overlap) {
                return response()->json([
                    'message' => 'تداخل زمانی با ' . $overlap['name'] . ' ' . $overlap['day'],
                    'code' => 'TIME_OVERLAP',
                    'errors' => ['conflicting_specs' => [$overlap['spec_id']]],
                ], 409);
            }

            $examOverlap = $this->findExamOverlap($user->id, $spec);
            if ($examOverlap) {
                return response()->json([
                    'message' => 'تداخل امتحان نهایی با ' . $examOverlap['name'] . ' - هر دو ' . $examOverlap['date'],
                    'code' => 'EXAM_OVERLAP',
                ], 409);
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
            'version' => 1,
        ]);

        // Prereq warnings (warn, never block — honor system)
        $warnings = $this->prereqWarnings($user->id, $spec);
        $warnings = array_merge($warnings, $this->coreqWarnings($user->id, $spec));

        if ($idempotencyKey) {
            IdempotencyKeys::create([
                'id' => Str::uuid(),
                'key' => $idempotencyKey,
                'user_id' => $user->id,
                'response_code' => 201,
                'response_body' => json_encode(['message' => 'به لیست موقت اضافه شد']),
                'expires_at' => now()->addHours(24),
            ]);
        }

        $enrollment->load(['specification.course', 'specification.professor']);
        $enrollment->shamsi_enrolled = ShamsiService::toShamsi($enrollment->enrolled_at);

        return response()->json([
            'message' => 'به لیست موقت اضافه شد',
            'enrollment' => $enrollment,
            'warnings' => $warnings,
        ], 201);
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

        $result = DB::transaction(function () use ($tempEnrollments, $user) {
            $finalized = 0;
            foreach ($tempEnrollments as $enr) {
                $enr->update([
                    'status' => 'finalized',
                    'finalized_at' => now(),
                    'version' => $enr->version + 1,
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
            return $finalized;
        });

        return response()->json(['message' => "انتخاب واحد نهایی شد ({$result} درس)"]);
    }

    public function myEnrollments(Request $request)
    {
        $user = $request->user();
        $current = Semester::where('is_current', true)->value('id');

        $query = Enrollment::where('student_id', $user->id)
            ->with(['specification.course', 'specification.professor', 'semester']);

        if ($request->boolean('archived')) {
            $query->where('status', 'archived');
        } else {
            $query->whereIn('status', ['temporary', 'finalized'])->where('semester_id', $current);
        }

        return response()->json($query->orderBy('enrolled_at')->get());
    }

    // ---- helpers ----

    private function currentCredits(string $studentId): int
    {
        return Enrollment::where('student_id', $studentId)
            ->where('status', 'temporary')
            ->with('specification.course')
            ->get()
            ->sum(fn ($e) => $e->specification->course->credits ?? 0);
    }

    private function findTimeOverlap(string $studentId, CourseSpecification $newSpec): ?array
    {
        $existing = Enrollment::where('student_id', $studentId)
            ->where('status', 'temporary')
            ->with('specification.course')
            ->get();

        foreach ($existing as $enr) {
            $old = $enr->specification;
            if ($old->day_of_week !== $newSpec->day_of_week) {
                continue;
            }
            if ($this->timesOverlap($old, $newSpec)) {
                return [
                    'name' => $old->course->name,
                    'day' => $old->day_of_week,
                    'spec_id' => $old->id,
                ];
            }
        }
        return null;
    }

    private function timesOverlap(CourseSpecification $a, CourseSpecification $b): bool
    {
        // Normalize overnight (is_next_day) to 24:00-based ranges.
        $aEnd = $a->is_next_day ? '24:00' : $a->time_end;
        $bEnd = $b->is_next_day ? '24:00' : $b->time_end;
        return max($a->time_start, $b->time_start) < min($aEnd, $bEnd);
    }

    private function findExamOverlap(string $studentId, CourseSpecification $newSpec): ?array
    {
        if (! $newSpec->exam_date_final_g) {
            return null;
        }
        $existing = Enrollment::where('student_id', $studentId)
            ->where('status', 'temporary')
            ->with('specification.course')
            ->get();

        $newExam = $newSpec->exam_date_final_g;
        foreach ($existing as $enr) {
            $oldExam = $enr->specification->exam_date_final_g;
            if (! $oldExam) {
                continue;
            }
            // Same Gregorian day + within 2h buffer
            if ($oldExam->toDateString() === $newExam->toDateString() && abs($oldExam->diffInMinutes($newExam)) < 120) {
                return [
                    'name' => $enr->specification->course->name,
                    'date' => $newExam->format('Y/m/d'),
                ];
            }
        }
        return null;
    }

    private function prereqWarnings(string $studentId, CourseSpecification $spec): array
    {
        $warnings = [];
        $prereqs = DB::table('course_prerequisites')->where('course_id', $spec->course_id)->pluck('prerequisite_id');
        foreach ($prereqs as $prereqId) {
            $passed = StudentPassedCourse::where('student_id', $studentId)
                ->where('course_id', $prereqId)
                ->where('passed', true)
                ->exists();
            if (! $passed) {
                $warnings[] = ['type' => 'prereq', 'course_id' => $prereqId, 'message' => "پیش‌نیاز {$prereqId} را پاس نکرده‌اید، ادامه می‌دهید؟"];
            }
        }
        return $warnings;
    }

    private function coreqWarnings(string $studentId, CourseSpecification $spec): array
    {
        $warnings = [];
        $coreqs = DB::table('course_corequisites')->where('course_id', $spec->course_id)->pluck('corequisite_id');
        foreach ($coreqs as $coreqId) {
            $inTemp = Enrollment::where('student_id', $studentId)->where('specification_id', $coreqId)->where('status', 'temporary')->exists();
            if (! $inTemp) {
                $warnings[] = ['type' => 'coreq', 'course_id' => $coreqId, 'message' => "هم‌نیاز {$coreqId} در لیست شما نیست"];
            }
        }
        return $warnings;
    }
}
