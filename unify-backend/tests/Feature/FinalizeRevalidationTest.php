<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Post-audit F-02: finalize() revalidates caps + overlaps against the CURRENT
 * declared status. The bypass was: declare high (24) → add units → re-declare
 * low (14) → finalize stamped the low status onto 24 units of load.
 */
class FinalizeRevalidationTest extends TestCase
{
    use RefreshDatabase;

    private function tempFor(User $student, array $specAttrs = [], int $credits = 3): Enrollment
    {
        $spec = CourseSpecification::factory()->create($specAttrs + [
            'day_of_week' => 'sat', 'time_start' => '08:00', 'time_end' => '10:00',
        ]);
        // Credits ride on the course row — keep each spec on its own course.
        $spec->course->update(['credits' => $credits]);

        return Enrollment::factory()->create([
            'student_id' => $student->id,
            'specification_id' => $spec->id,
            'semester_id' => $spec->semester_id,
            'status' => 'temporary',
        ]);
    }

    public function test_finalize_blocked_when_current_status_cap_is_exceeded(): void
    {
        // Declared gpa_a (24) while adding 21 units…
        $student = User::factory()->create(['role' => 'student', 'academic_status_declared' => 'gpa_a']);
        foreach (['sat' => '08:00', 'sun' => '10:00', 'mon' => '13:00', 'tue' => '08:00', 'wed' => '10:00', 'thu' => '13:00', 'sat2' => '10:00'] as $day => $start) {
            $this->tempFor($student, [
                'day_of_week' => str_starts_with($day, 'sat') ? 'sat' : $day,
                'time_start' => $start,
                'time_end' => \Carbon\Carbon::parse($start)->addHours(2)->format('H:i'),
            ]);
        }

        // …then quietly re-declared conditional (14): finalize must refuse.
        $student->update(['academic_status_declared' => 'conditional']);

        $this->actingAs($student)->postJson('/api/v1/enrollment/final')
            ->assertStatus(422)
            ->assertJson(['code' => 'CREDIT_LIMIT_EXCEEDED']);
    }

    public function test_finalize_blocked_when_overlap_appears_after_downgrade(): void
    {
        // final_semester IGNORES overlap rules at add time — overlapping temps.
        $student = User::factory()->create(['role' => 'student', 'academic_status_declared' => 'final_semester']);
        $this->tempFor($student, ['day_of_week' => 'mon', 'time_start' => '08:00', 'time_end' => '10:00']);
        $this->tempFor($student, ['day_of_week' => 'mon', 'time_start' => '09:00', 'time_end' => '11:00']);

        $student->update(['academic_status_declared' => 'normal']);

        $this->actingAs($student)->postJson('/api/v1/enrollment/final')
            ->assertStatus(422)
            ->assertJson(['code' => 'TIME_OVERLAP']);
    }

    public function test_finalize_allowed_when_status_unchanged_and_clean(): void
    {
        $student = User::factory()->create(['role' => 'student', 'academic_status_declared' => 'normal']);
        $this->tempFor($student, ['day_of_week' => 'sat', 'time_start' => '08:00', 'time_end' => '10:00']);
        $this->tempFor($student, ['day_of_week' => 'sun', 'time_start' => '10:00', 'time_end' => '12:00']);

        $this->actingAs($student)->postJson('/api/v1/enrollment/final')
            ->assertStatus(200);
    }

    public function test_cross_midnight_overlap_is_caught(): void
    {
        // Class A: sat 22:00 → 01:00 (next day). Class B: SUN 00:30–02:00.
        // Same-day-only checks structurally miss this pair.
        $student = User::factory()->create(['role' => 'student', 'academic_status_declared' => 'normal']);
        $this->tempFor($student, ['day_of_week' => 'sat', 'time_start' => '22:00', 'time_end' => '01:00', 'is_next_day' => true]);
        $this->tempFor($student, ['day_of_week' => 'sun', 'time_start' => '00:30', 'time_end' => '02:00', 'is_next_day' => false]);

        $this->actingAs($student)->postJson('/api/v1/enrollment/final')
            ->assertStatus(422)
            ->assertJson(['code' => 'TIME_OVERLAP']);
    }
}
