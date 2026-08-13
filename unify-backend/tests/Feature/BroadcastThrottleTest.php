<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BroadcastThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_cannot_broadcast_too_frequently()
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $spec = CourseSpecification::factory()->create();

        // First broadcast
        $this->actingAs($professor)->postJson('/api/v1/messages/send', [
            'specification_id' => $spec->id,
            'subject' => 'Test',
            'body' => 'Message 1'
        ]);

        // Second broadcast immediately
        $response = $this->actingAs($professor)->postJson('/api/v1/messages/send', [
            'specification_id' => $spec->id,
            'subject' => 'Test',
            'body' => 'Message 2'
        ]);

        $response->assertStatus(429);
    }

    /**
     * Round-2 (V2-13): retry_after is the EXACT remainder in seconds — no
     * 60s floor padding at the tail of the window.
     */
    public function test_throttled_response_carries_exact_retry_after(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $spec = CourseSpecification::factory()->create();

        $this->actingAs($professor)->postJson('/api/v1/messages/send', [
            'specification_id' => $spec->id,
            'subject' => 't', 'body' => 'one',
        ])->assertStatus(201);

        $response = $this->actingAs($professor)->postJson('/api/v1/messages/send', [
            'specification_id' => $spec->id,
            'subject' => 't', 'body' => 'two',
        ])->assertStatus(429);

        $retry = $response->json('retry_after');
        $this->assertNotNull($retry);
        $this->assertGreaterThanOrEqual(1, (int) $retry);
        $this->assertLessThanOrEqual(600, (int) $retry);
    }
}