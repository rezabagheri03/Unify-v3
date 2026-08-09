<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_unread_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/notifications/unread');

        $response->assertStatus(200);
    }

    public function test_user_can_mute_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/notifications/mute', [
            'specification_id' => 'spec-123',
            'muted' => true
        ]);

        $response->assertStatus(200);
    }
}