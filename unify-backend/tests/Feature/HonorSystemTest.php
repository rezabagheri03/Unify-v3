<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HonorSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_declare_honor_status()
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->postJson('/api/v1/users/me/academic-status', [
            'status' => 'final_semester',
            'acknowledged' => true,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('final_semester', $user->fresh()->academic_status_declared);
    }
}