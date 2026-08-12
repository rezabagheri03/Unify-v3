<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_ticket()
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->postJson('/api/v1/tickets', [
            'department' => 'education',
            'subject' => 'Problem with enrollment',
            'description' => 'I cannot add a course'
        ]);

        $response->assertStatus(201);
    }

    public function test_ticket_escalates_after_48_hours()
    {
        $ticket = \App\Models\Ticket::factory()->create([
            'status' => 'open',
            'updated_at' => now()->subHours(49)
        ]);

        $this->artisan('tickets:escalate');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'is_escalated' => true
        ]);
    }
}