<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResourceRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_rate_resource()
    {
        $student = User::factory()->create(['role' => 'student']);
        $resource = Resource::factory()->create();

        $response = $this->actingAs($student)->postJson("/api/v1/resources/{$resource->id}/rating", [
            'rating' => 5
        ]);

        $response->assertStatus(200);
    }
}