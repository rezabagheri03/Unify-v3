<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MessageBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_broadcast_to_specification()
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $spec = \App\Models\CourseSpecification::factory()->create(['professor_id' => $professor->id]);

        $response = $this->actingAs($professor)->postJson('/api/v1/messages/send', [
            'specification_id' => $spec->id,
            'subject' => 'Announcement',
            'body' => 'Class cancelled'
        ]);

        $response->assertStatus(201);
    }
}