<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssignmentTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_assignment()
    {
        $student = User::factory()->create(['role' => 'student']);
        $spec = CourseSpecification::factory()->create();

        $response = $this->actingAs($student)->postJson('/api/v1/assignments', [
            'specification_id' => $spec->id,
            'title' => 'Homework 1',
            'due_date_shamsi' => '1403/08/15'
        ]);

        $response->assertStatus(201);
    }
}