<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\IdempotencyKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IdempotencyCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_keys_are_cleaned()
    {
        IdempotencyKeys::factory()->create([
            'expires_at' => now()->subDay()
        ]);

        $this->artisan('idempotency:cleanup');

        $this->assertDatabaseCount('idempotency_keys', 0);
    }
}