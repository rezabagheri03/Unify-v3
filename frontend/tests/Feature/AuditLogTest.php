<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_audit_logs()
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $response = $this->actingAs($owner)->getJson('/api/owner/audit-logs');

        $response->assertStatus(200);
    }
}