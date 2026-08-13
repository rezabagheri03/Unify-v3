<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResourceVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_create_new_version()
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $resource = Resource::factory()->create([
            'professor_id' => $professor->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($professor)->postJson("/api/v1/resources/{$resource->id}/new-version", [
            'file' => $this->fakePdf('new.pdf')
        ]);

        $response->assertStatus(201);
    }
}