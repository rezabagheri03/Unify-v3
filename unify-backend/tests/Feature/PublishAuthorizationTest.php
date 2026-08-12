<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Negative-authorization matrix (Security audit SEC-02 / TODO-044).
 *
 * These tests pin the route-layer role guards that stopped students from
 * publishing official content and broadcasting to classes. They run against
 * SQLite; route/middleware behavior is driver-independent.
 */
class PublishAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function userAs(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_student_cannot_publish_forms(): void
    {
        $this->actingAs($this->userAs('student'))
            ->postJson('/api/v1/forms', ['title' => 'x'])
            ->assertStatus(403);
    }

    public function test_student_cannot_publish_faq_or_calendar(): void
    {
        $student = $this->userAs('student');
        $this->actingAs($student)->postJson('/api/v1/faqs', ['question' => 'q', 'answer' => 'a'])->assertStatus(403);
        $this->actingAs($student)->postJson('/api/v1/academic-calendar', ['title' => 't'])->assertStatus(403);
    }

    public function test_student_cannot_publish_notice_board(): void
    {
        $this->actingAs($this->userAs('student'))
            ->postJson('/api/v1/notice-board', ['title' => 'x', 'body' => 'y'])
            ->assertStatus(403);
    }

    public function test_professor_cannot_publish_forms(): void
    {
        // ROLES: forms are Admin (university) + Expert (department) only.
        $this->actingAs($this->userAs('professor'))
            ->postJson('/api/v1/forms', ['title' => 'x'])
            ->assertStatus(403);
    }

    public function test_student_cannot_broadcast_to_class(): void
    {
        // The spec must really exist: MessageController validates BEFORE the
        // broadcast role check, so a nonexistent id returns 422 instead of the
        // 403 this test pins (a hardcoded id previously leaked from a dev DB).
        $spec = \App\Models\CourseSpecification::factory()->create();

        $this->actingAs($this->userAs('student'))
            ->postJson('/api/v1/messages/send', [
                'subject' => 's',
                'body' => 'b',
                // specification_id without recipient => broadcast attempt
                'specification_id' => $spec->id,
            ])
            ->assertStatus(403);
    }

    public function test_message_send_rejects_invalid_priority(): void
    {
        $this->actingAs($this->userAs('professor'))
            ->postJson('/api/v1/messages/send', [
                'subject' => 's',
                'body' => 'b',
                'priority' => 'CRITICAL-HACK',
            ])
            ->assertStatus(422);
    }
}
