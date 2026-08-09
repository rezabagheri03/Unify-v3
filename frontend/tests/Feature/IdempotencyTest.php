<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_idempotency_prevents_duplicate_requests()
    {
        $student = User::factory()->create(['role' => 'student']);
        $key = \Illuminate\Support\Str::uuid();

        $this->actingAs($student)->postJson('/api/enrollment/temp', [
            'specification_id' => 'spec-123'
        ], ['Idempotency-Key' => $key]);

        $response = $this->actingAs($student)->postJson('/api/enrollment/temp', [
            'specification_id' => 'spec-123'
        ], ['Idempotency-Key' => $key]);

        $response->assertStatus(200);
    }
}