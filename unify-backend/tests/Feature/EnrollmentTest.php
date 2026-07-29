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

        $this->actingAs($student)->postJson('/api/enrollment/temp', [
            'specification_id' => $spec1->id
        ]);

        $response = $this->actingAs($student)->postJson('/api/enrollment/temp', [
            'specification_id' => $spec2->id
        ]);

        $response->assertStatus(409);
    }

    public function test_student_can_finalize_enrollments()
    {
        $student = User::factory()->create(['role' => 'student']);
        
        \App\Models\Enrollment::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'temporary'
        ]);

        $response = $this->actingAs($student)->postJson('/api/enrollment/final');

        $response->assertStatus(200);
    }
}