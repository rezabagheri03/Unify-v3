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
        $student = User::factory()->create(['role' => 'student', 'academic_status_declared' => 'normal']);
        $spec = \App\Models\CourseSpecification::factory()->create();
        $key = \Illuminate\Support\Str::uuid();

        $this->actingAs($student)->postJson('/api/v1/enrollment/temp', [
            'specification_id' => $spec->id
        ], ['Idempotency-Key' => $key])->assertStatus(201);

        // Same key -> returns the cached response, no duplicate row.
        $response = $this->actingAs($student)->postJson('/api/v1/enrollment/temp', [
            'specification_id' => $spec->id
        ], ['Idempotency-Key' => $key]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('enrollments', 1);
    }
}