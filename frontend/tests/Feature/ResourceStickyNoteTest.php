<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResourceStickyNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_add_sticky_note()
    {
        $student = User::factory()->create(['role' => 'student']);
        $resource = Resource::factory()->create();

        $response = $this->actingAs($student)->postJson("/api/resources/{$resource->id}/sticky-note", [
            'note' => 'Private note'
        ]);

        $response->assertStatus(200);
    }
}