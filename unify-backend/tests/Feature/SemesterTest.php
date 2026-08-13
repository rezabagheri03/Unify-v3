<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SemesterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_new_semester()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/semesters', [
            'name' => '1403-2',
            'start_shamsi' => '1403/07/01',
            'end_shamsi' => '1404/01/31',
            'is_current' => true
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('semesters', ['name' => '1403-2']);
    }
}