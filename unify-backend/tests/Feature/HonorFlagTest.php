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

        // Declare final semester 3 times in different semesters
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($student)->postJson('/api/users/me/academic-status', [
                'status' => 'final_semester',
                'acknowledged' => true
            ]);
        }

        $this->assertDatabaseHas('honor_flags', [
            'student_id' => $student->id,
            'flag_type' => 'final_semester_abuse'
        ]);
    }
}