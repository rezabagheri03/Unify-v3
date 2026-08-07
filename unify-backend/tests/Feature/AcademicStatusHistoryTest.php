<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AcademicStatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_semester_declaration_creates_history()
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->postJson('/api/v1/users/me/academic-status', [
            'status' => 'final_semester',
            'acknowledged' => true
        ]);

        $this->assertDatabaseHas('academic_status_history', [
            'student_id' => $student->id,
            'status' => 'final_semester'
        ]);
    }
}