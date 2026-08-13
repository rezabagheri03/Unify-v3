<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Post-audit F-04: the audit trail must actually be fed. Only logins used to
 * write rows; ban/unban are pinned here as the representative privileged pair.
 */
class AuditProducersTest extends TestCase
{
    use RefreshDatabase;

    public function test_ban_and_unban_write_audit_rows(): void
    {
        $owner = User::factory()->owner()->create();
        $target = User::factory()->create();

        $this->actingAs($owner)->postJson("/api/v1/owner/users/{$target->id}/ban", ['reason' => 'تقلب'])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_banned',
            'resource_type' => 'user',
            'resource_id' => $target->id,
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)->postJson("/api/v1/owner/users/{$target->id}/unban")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_unbanned',
            'resource_id' => $target->id,
        ]);

        $this->assertGreaterThanOrEqual(2, AuditLog::count());
    }
}
