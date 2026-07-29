<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnrollmentTempTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_add_to_temporary_enrollment()
    {
        $student = User::factory()->create(['role' => 'student']);
        $spec = CourseSpecification::factory()->create();

        $response = $this->actingAs($student)->postJson('/api/enrollment/temp', [
            'specification_id' => $spec->id
        ]);

        $response->assertStatus(201);
    }
}