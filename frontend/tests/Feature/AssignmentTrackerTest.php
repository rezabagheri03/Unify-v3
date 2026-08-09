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

        $response = $this->actingAs($student)->postJson('/api/assignments', [
            'specification_id' => $spec->id,
            'title' => 'Homework 1',
            'due_date_g' => now()->addWeek()
        ]);

        $response->assertStatus(201);
    }
}