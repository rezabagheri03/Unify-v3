<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_sync_offline_items()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/offline/sync', [
            'items' => [
                [
                    'type' => 'rating',
                    'payload' => ['resource_family_id' => 'uuid-123', 'rating' => 5],
                    'idempotency_key' => \Illuminate\Support\Str::uuid()
                ]
            ]
        ]);

        $response->assertStatus(200);
    }
}