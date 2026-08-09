<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentTempRequest;
use App\Models\CourseSpecification;
use App\Models\Enrollment;
use App\Models\IdempotencyKeys;
use App\Models\AcademicStatusHistory;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentController extends Controller
{
    public function indexTemp(Request $request)
    {
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->where('status', 'temporary')
            ->with(['specification.course', 'specification.professor'])
            ->get();

        return response()->json($enrollments);
    }

    public function storeTemp(StoreEnrollmentTempRequest $request)
    {
        $user = $request->user();
        $specId = $request->specification_id;

        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey) {
            $existing = IdempotencyKeys::where('key', $idempotencyKey)->first();
            if ($existing) {
                return response()->json(json_decode($existing->response_body, true), $existing->response_code);
            }
        }

        $spec = CourseSpecification::findOrFail($specId);

        $overlap = $this->checkTimeOverlap($user->id, $spec);
        if ($overlap) {
            return response()->json([
                'message' => 'تداخل زمانی با ' . $overlap,
                'reason' => 'time_overlap'
            ], 409);
        }

        if ($this->checkExamOverlap($user->id, $spec)) {
            return response()->json(['message' => 'تداخل امتحان', 'reason' => 'exam_overlap'], 409);
        }

        $currentCredits = $this->getCurrentCredits($user->id);
        $maxCredits = $this->getMaxCredits($user->academic_status_declared);
        if ($currentCredits + $spec->course->credits > $maxCredits) {
            return response()->json(['message' => 'حداکثر واحد مجاز', 'reason' => 'credit_limit'], 409);
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

        if ($idempotencyKey) {
            IdempotencyKeys::create([
                'id' => Str::uuid(),
                'key' => $idempotencyKey,
                'user_id' => $user->id,
                'response_code' => 201,
                'response_body' => json_encode(['message' => 'Added to temp']),
                'expires_at' => now()->addHours(24),
            ]);
        }

        // Use ShamsiService for consistent date handling
        $enrollment->shamsi_enrolled = ShamsiService::toShamsi($enrollment->enrolled_at);

        return response()->json(['message' => 'به لیست موقت اضافه شد'], 201);
    }

    private function checkTimeOverlap(string $studentId, CourseSpecification $newSpec): ?string
    {
        $existing = Enrollment::where('student_id', $studentId)
            ->where('status', 'temporary')
            ->with('specification')
            ->get();

        foreach ($existing as $enr) {
            $old = $enr->specification;
            if ($old->day_of_week !== $newSpec->day_of_week) continue;

            $oldStart = $old->time_start;
            $oldEnd = $old->is_next_day ? '24:00' : $old->time_end;
            $newStart = $newSpec->time_start;
            $newEnd = $newSpec->is_next_day ? '24:00' : $newSpec->time_end;

            if ($this->timesOverlap($oldStart, $oldEnd, $newStart, $newEnd)) {
                return $old->course->name . ' ' . $old->day_of_week;
            }
        }
        return null;
    }

    private function timesOverlap($start1, $end1, $start2, $end2): bool
    {
        return max($start1, $start2) < min($end1, $end2);
    }

    private function checkExamOverlap(string $studentId, CourseSpecification $newSpec): bool
    {
        return false;
    }

    private function getCurrentCredits(string $studentId): int
    {
        return Enrollment::where('student_id', $studentId)
            ->where('status', 'temporary')
            ->with('specification.course')
            ->get()
            ->sum(fn($e) => $e->specification->course->credits ?? 0);
    }

    private function getMaxCredits(?string $status): int
    {
        return match($status) {
            'final_semester' => 24,
            'gpa_a' => 24,
            'conditional' => 14,
            default => 20,
        };
    }

    public function finalize(Request $request)
    {
        $user = $request->user();

        $semester = \App\Models\Semester::where('is_current', true)->first();
        if ($semester && $semester->grace_period_ends_at && now()->greaterThan($semester->grace_period_ends_at) && !$semester->grace_period_handled) {
            \Artisan::call('enrollments:wipe-grace');
            return response()->json(['message' => 'دوره grace تمام شده است'], 403);
        }

        if (!$user->academic_status_declared) {
            return response()->json(['message' => 'وضعیت تحصیلی خود را اعلام کنید'], 400);
        }

        $tempEnrollments = Enrollment::where('student_id', $user->id)
            ->where('status', 'temporary')
            ->get();

        DB::transaction(function () use ($tempEnrollments, $user) {
            foreach ($tempEnrollments as $enr) {
                $enr->update([
                    'status' => 'finalized',
                    'finalized_at' => now(),
                    'academic_status_at_enrollment' => $user->academic_status_declared,
                ]);

                if ($user->academic_status_declared === 'final_semester') {
                    AcademicStatusHistory::create([
                        'id' => Str::uuid(),
                        'student_id' => $user->id,
                        'status' => 'final_semester',
                        'semester_id' => $enr->semester_id,
                        'declared_at' => now(),
                    ]);
                }
            }
        });

        return response()->json(['message' => 'انتخاب واحد نهایی شد']);
    }
}