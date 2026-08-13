<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnrollmentFinalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_finalize_temporary_enrollments()
    {
        $student = User::factory()->create(['role' => 'student']);

        // Deterministic, non-overlapping specs — finalize() now revalidates
        // time overlaps (post-audit F-02), so random factory times would flake.
        foreach (['sat', 'sun', 'mon'] as $day) {
            $spec = CourseSpecification::factory()->create([
                'day_of_week' => $day, 'time_start' => '08:00', 'time_end' => '10:00',
            ]);
            Enrollment::factory()->create([
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
