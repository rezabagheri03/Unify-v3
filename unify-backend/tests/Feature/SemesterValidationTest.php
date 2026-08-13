<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Round-2 (V2-10): semester creation fails with 422s (not 500s) on bad admin
 * input, and the audit row records the REAL semester id.
 */
class SemesterValidationTest extends TestCase
{
    use RefreshDatabase;

    private function expert(): User
    {
        return User::factory()->create(['role' => 'expert']);
    }

    public function test_invalid_shamsi_date_is_a_422(): void
    {
        $this->actingAs($this->expert())->postJson('/api/v1/admin/semesters', [
            'name' => '1404-1', 'start_shamsi' => 'garbage', 'end_shamsi' => '1403/12/01',
        ])->assertStatus(422)->assertJson(['code' => 'BAD_DATE']);
    }

    public function test_duplicate_name_is_a_422(): void
    {
        $this->actingAs($this->expert())->postJson('/api/v1/admin/semesters', [
            'name' => '1403-1', 'start_shamsi' => '1403/06/25', 'end_shamsi' => '1404/04/01',
        ])->assertStatus(422)->assertJson(['code' => 'DUPLICATE_SEMESTER']);
    }

    public function test_end_before_start_is_a_422(): void
    {
        $this->actingAs($this->expert())->postJson('/api/v1/admin/semesters', [
            'name' => '1404-2', 'start_shamsi' => '1403/07/01', 'end_shamsi' => '1403/06/01',
        ])->assertStatus(422)->assertJson(['code' => 'BAD_RANGE']);
    }

    public function test_successful_creation_audits_the_real_semester_id(): void
    {
        $this->actingAs($this->expert())->postJson('/api/v1/admin/semesters', [
            'name' => '1404-1', 'start_shamsi' => '1403/06/25', 'end_shamsi' => '1404/04/01',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'semester_created',
            'resource_type' => 'semester',
            'resource_id' => '1404-1',
        ]);
        $this->assertDatabaseHas('semesters', ['id' => '1404-1', 'is_current' => true]);
    }
}
