<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Round-2 (V2-17): mutes are honored — a notification bound to a muted spec
 * (data->specification_id) never reaches the unread feed, and unmuting brings
 * it straight back (cache invalidated on mute change).
 */
class MuteFilterTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, ?string $specId, string $title): Notification
    {
        return Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'type' => 'general',
            'title' => $title,
            'body' => 'b',
            'priority' => 'low',
            'read' => false,
            'data' => $specId ? ['specification_id' => $specId] : null,
            'created_at' => now(),
        ]);
    }

    public function test_muted_spec_notifications_are_filtered_and_unmute_restores(): void
    {
        $user = User::factory()->create();
        $spec = CourseSpecification::factory()->create();

        $muted = $this->notify($user, $spec->id, 'class news');
        $plain = $this->notify($user, null, 'general news');

        $this->actingAs($user)->postJson('/api/v1/notifications/mute', [
            'specification_id' => $spec->id, 'muted' => true,
        ])->assertStatus(200);

        $resp = $this->actingAs($user)->getJson('/api/v1/notifications/unread')->assertStatus(200);
        $titles = collect($resp->json())->pluck('title');
        $this->assertFalse($titles->contains('class news'));
        $this->assertTrue($titles->contains('general news'));

        $this->actingAs($user)->postJson('/api/v1/notifications/mute', [
            'specification_id' => $spec->id, 'muted' => false,
        ])->assertStatus(200);

        $resp = $this->actingAs($user)->getJson('/api/v1/notifications/unread')->assertStatus(200);
        $this->assertTrue(collect($resp->json())->pluck('title')->contains('class news'));
    }
}
