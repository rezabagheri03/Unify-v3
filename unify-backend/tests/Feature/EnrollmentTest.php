<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_enroll_in_overlapping_time()
    {
        $student = User::factory()->create(['role' => 'student']);
        
        $spec1 = CourseSpecification::factory()->create([
            'day_of_week' => 'sat',
            'time_start' => '08:00',
            'time_end' => '10:00'
        ]);
        
        $spec2 = CourseSpecification::factory()->create([
            'day_of_week' => 'sat',
            'time_start' => '09:00',
            'time_end' => '11:00'
        ]);

        $this->actingAs($student)->postJson('/api/v1/enrollment/temp', [
            'specification_id' => $spec1->id
        ]);

        $response = $this->actingAs($student)->postJson('/api/v1/enrollment/temp', [
            'specification_id' => $spec2->id
        ]);

        $response->assertStatus(409);
    }

    public function test_student_can_finalize_enrollments()
    {
        $student = User::factory()->create(['role' => 'student']);

        // Deterministic, non-overlapping specs — finalize() revalidates time
        // overlaps against the current declared status (post-audit F-02), so
        // random factory day/times made this test flaky (CI caught it).
        foreach (['sat', 'sun', 'mon'] as $day) {
            $spec = CourseSpecification::factory()->create([
                'day_of_week' => $day, 'time_start' => '08:00', 'time_end' => '10:00',
            ]);
            \App\Models\Enrollment::factory()->create([
                'student_id' => $student->id,
                'specification_id' => $spec->id,
                'semester_id' => $spec->semester_id,
                'status' => 'temporary',
            ]);
        }

        $response = $this->actingAs($student)->postJson('/api/v1/enrollment/final');

        $response->assertStatus(200);
    }
}