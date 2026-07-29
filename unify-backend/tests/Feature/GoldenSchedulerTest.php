<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoldenSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_get_golden_schedules()
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->getJson('/api/golden-schedule');

        $response->assertStatus(200)
                 ->assertJsonStructure(['suggestions']);
    }
}