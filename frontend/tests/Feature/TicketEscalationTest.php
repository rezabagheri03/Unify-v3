<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_escalates_after_48_hours()
    {
        $ticket = Ticket::factory()->create([
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