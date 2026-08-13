<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\Resource;
use App\Models\AssignmentTracker;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Offline-sync IDOR + validation matrix (Security audit / TODO-044).
 *
 * The sync endpoint must never trust client payloads: every item is
 * validated per type, authorized against the CALLER (not the claimed
 * student_id), and replayed per-user via its idempotency key.
 */
class OfflineSyncSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function syncItem(User $user, string $type, array $payload, ?string $key = null): array
    {
        $response = $this->actingAs($user)->postJson('/api/v1/offline/sync', [
            'items' => [[
                'type' => $type,
                'payload' => $payload,
                'idempotency_key' => $key ?? (string) Str::uuid(),
            ]],
        ]);
        $response->assertStatus(200);
        return $response->json('results.0');
    }

    public function test_cannot_reply_to_another_users_ticket(): void
    {
        $victim = User::factory()->create(['role' => 'student']);
        $attacker = User::factory()->create(['role' => 'student']);
        $ticket = Ticket::factory()->create(['student_id' => $victim->id]);

        $result = $this->syncItem($attacker, 'ticket_reply', [
            'ticket_id' => $ticket->id,
            'body' => 'hijacked reply',
        ]);

        $this->assertSame('failed', $result['status']);
        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(0, TicketReply::count());
    }

    public function test_can_reply_to_own_ticket(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $ticket = Ticket::factory()->create(['student_id' => $user->id]);

        $result = $this->syncItem($user, 'ticket_reply', [
            'ticket_id' => $ticket->id,
            'body' => 'my legitimate reply',
        ]);

        $this->assertSame('synced', $result['status']);
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'sender_id' => $user->id,
        ]);
    }

    public function test_cannot_hijack_another_users_assignment_tracker(): void
    {
        $victim = User::factory()->create(['role' => 'student']);
        $attacker = User::factory()->create(['role' => 'student']);
        $spec = CourseSpecification::factory()->create();
        $tracker = AssignmentTracker::create([
            'id' => (string) Str::uuid(),
            'student_id' => $victim->id,
            'specification_id' => $spec->id,
            'title' => 'victim tracker',
            'due_date_g' => now()->addDays(7),
        ]);

        $result = $this->syncItem($attacker, 'assignment', [
            'id' => $tracker->id,
            'specification_id' => $spec->id,
            'title' => 'hijacked title',
            'status' => 'done',
        ]);

        $this->assertSame('failed', $result['status']);
        $this->assertSame('forbidden', $result['error']);
        $this->assertSame('victim tracker', $tracker->fresh()->title);
    }

    public function test_out_of_range_rating_fails_validation(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $resource = Resource::factory()->create();

        $result = $this->syncItem($user, 'rating', [
            'resource_family_id' => $resource->id,
            'rating' => 9,
        ]);

        $this->assertSame('failed', $result['status']);
        $this->assertSame('invalid_payload', $result['error']);
        $this->assertSame(0, \App\Models\ResourceRating::count());
    }

    public function test_replaying_idempotency_key_returns_duplicate(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $resource = Resource::factory()->create();
        $key = (string) Str::uuid();
        $payload = ['resource_family_id' => $resource->id, 'rating' => 4];

        $first = $this->syncItem($user, 'rating', $payload, $key);
        $second = $this->syncItem($user, 'rating', $payload, $key);

        $this->assertSame('synced', $first['status']);
        $this->assertSame('duplicate', $second['status']);
        // upsert semantics: still exactly one rating row for (student, family)
        $this->assertSame(1, \App\Models\ResourceRating::where('student_id', $user->id)->count());
    }
}
