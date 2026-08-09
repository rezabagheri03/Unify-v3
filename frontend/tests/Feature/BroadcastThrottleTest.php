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
        $this->actingAs($professor)->postJson('/api/messages/send', [
            'specification_id' => $spec->id,
            'subject' => 'Test',
            'body' => 'Message 1'
        ]);

        // Second broadcast immediately
        $response = $this->actingAs($professor)->postJson('/api/messages/send', [
            'specification_id' => $spec->id,
            'subject' => 'Test',
            'body' => 'Message 2'
        ]);

        $response->assertStatus(429);
    }
}