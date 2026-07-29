<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_device_token()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/devices', [
            'token' => 'test-token-123',
            'provider' => 'pushe',
            'platform' => 'android'
        ]);

        $response->assertStatus(200);
    }
}