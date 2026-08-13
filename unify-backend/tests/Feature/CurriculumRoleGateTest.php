<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Round-2 (V2-04): chart authoring is staff-only; students can neither create
 * nor submit-for-approval (the head-notification trigger).
 */
class CurriculumRoleGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_create_chart(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->postJson('/api/v1/curriculum', [
            'entry_year' => 1401,
            'chart_data' => [],
        ])->assertStatus(403);
    }

    public function test_expert_creates_and_submits_chart(): void
    {
        $expert = User::factory()->create(['role' => 'expert', 'department_id' => 'CS']);
        User::factory()->create(['role' => 'head_of_dept', 'department_id' => 'CS']);

        $resp = $this->actingAs($expert)->postJson('/api/v1/curriculum', [
            'entry_year' => 1401,
            'chart_data' => [['course_id' => 'X']],
        ])->assertStatus(201);

        $this->actingAs($expert)->postJson('/api/v1/curriculum/' . $resp->json('id') . '/submit')->assertStatus(200);
    }

    public function test_student_cannot_submit_someone_elses_chart(): void
    {
        $expert = User::factory()->create(['role' => 'expert', 'department_id' => 'CS']);
        $resp = $this->actingAs($expert)->postJson('/api/v1/curriculum', [
            'entry_year' => 1401,
            'chart_data' => [['course_id' => 'Y']],
        ])->assertStatus(201);

        $student = User::factory()->create(['role' => 'student', 'department_id' => 'CS']);
        $this->actingAs($student)->postJson('/api/v1/curriculum/' . $resp->json('id') . '/submit')->assertStatus(403);
    }
}
