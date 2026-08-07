<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StorageStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_storage_usage()
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $response = $this->actingAs($owner)->getJson('/api/v1/monitoring/storage');

        $response->assertStatus(200)
                 ->assertJsonStructure(['used_bytes', 'used_gb', 'limit_gb', 'percentage']);
    }
}