<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MessageBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_broadcast_to_specification()
    {
        $professor = User::factory()->create(['role' => 'professor']);

        $response = $this->actingAs($professor)->postJson('/api/messages/send', [
            'specification_id' => 'spec-123',
            'subject' => 'Announcement',
            'body' => 'Class cancelled'
        ]);

        $response->assertStatus(201);
    }
}