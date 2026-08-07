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

        $response = $this->actingAs($user)->getJson('/api/v1/notifications/unread');

        $response->assertStatus(200);
    }

    public function test_user_can_mute_notifications()
    {
        $user = User::factory()->create();
        $spec = \App\Models\CourseSpecification::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/notifications/mute', [
            'specification_id' => $spec->id,
            'muted' => true
        ]);

        $response->assertStatus(200);
    }
}