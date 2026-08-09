<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_message()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/api/messages/send', [
            'recipient_id' => $recipient->id,
            'subject' => 'Test',
            'body' => 'Hello'
        ]);

        $response->assertStatus(201);
    }
}