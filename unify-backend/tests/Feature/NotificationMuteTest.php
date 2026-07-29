<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationMuteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mute_notifications_for_specification()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/notifications/mute', [
            'specification_id' => 'spec-123',
            'muted' => true
        ]);

        $response->assertStatus(200);
    }
}