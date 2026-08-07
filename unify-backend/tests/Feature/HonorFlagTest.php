<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HonorFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_semester_abuse_creates_flag()
    {
        $student = User::factory()->create(['role' => 'student']);

        // Declare final semester across 3 DISTINCT semesters (abuse rule: >2).
        for ($i = 0; $i < 3; $i++) {
            \App\Models\Semester::query()->update(['is_current' => false]);
            \App\Models\Semester::create([
                'id' => 'test-sem-' . $i,
                'name' => 'Semester ' . $i,
                'is_current' => true,
                'global_state' => 'enrolling',
                'start_date_g' => '2024-09-20 08:00:00',
                'end_date_g' => '2025-01-20 18:00:00',
            ]);
            $this->actingAs($student)->postJson('/api/v1/users/me/academic-status', [
                'status' => 'final_semester',
                'acknowledged' => true
            ])->assertStatus(200);
        }

        $this->assertDatabaseHas('honor_flags', [
            'student_id' => $student->id,
            'flag_type' => 'final_semester_abuse'
        ]);
    }
}