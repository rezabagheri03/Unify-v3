<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnrollmentFinalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_finalize_temporary_enrollments()
    {
        $student = User::factory()->create(['role' => 'student']);
        Enrollment::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'temporary'
        ]);

        $response = $this->actingAs($student)->postJson('/api/enrollment/final');

        $response->assertStatus(200);
    }
}