<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CourseSpecificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_list_specifications()
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->getJson('/api/specifications');

        $response->assertStatus(200);
    }
}