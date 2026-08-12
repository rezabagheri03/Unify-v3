<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurriculumChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_expert_can_create_curriculum_chart()
    {
        $expert = User::factory()->create(['role' => 'expert']);

        $response = $this->actingAs($expert)->postJson('/api/v1/curriculum', [
            'department_id' => 'CS',
            'entry_year' => 1401,
            'chart_data' => ['semesters' => []]
        ]);

        $response->assertStatus(201);
    }
}