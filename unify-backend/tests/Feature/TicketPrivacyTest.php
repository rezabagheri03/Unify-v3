<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Round-2 (V2-03): ticket payloads embed users trimmed to User::PUBLIC_COLS —
 * mobile/email/supplementary_details never cross the wire either direction.
 */
class TicketPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const PII = ['mobile', 'email', 'supplementary_details'];

    public function test_staff_viewing_a_ticket_gets_no_student_pii(): void
    {
        $student = User::factory()->create(['mobile' => '09120000000', 'email' => 's@x.ir']);
        $expert = User::factory()->create(['role' => 'expert', 'ticket_lane' => 'education']);
        $ticket = Ticket::factory()->create(['student_id' => $student->id, 'department' => 'education']);

        $resp = $this->actingAs($expert)->getJson('/api/v1/tickets/' . $ticket->id)->assertStatus(200);
        foreach (self::PII as $field) {
            $this->assertArrayNotHasKey($field, $resp->json('student'), "student.{$field} leaked to staff");
        }
    }

    public function test_student_reading_replies_gets_no_staff_pii(): void
    {
        $student = User::factory()->create();
        $expert = User::factory()->create(['role' => 'expert', 'ticket_lane' => 'education', 'mobile' => '09121111111', 'email' => 'e@x.ir']);
        $ticket = Ticket::factory()->create(['student_id' => $student->id, 'department' => 'education']);

        $this->actingAs($expert)->postJson('/api/v1/tickets/' . $ticket->id . '/reply', ['body' => 'جواب'])->assertStatus(201);

        $resp = $this->actingAs($student)->getJson('/api/v1/tickets/' . $ticket->id)->assertStatus(200);
        $senders = collect($resp->json('replies'))->pluck('sender');
        $this->assertNotEmpty($senders);
        foreach (self::PII as $field) {
            $this->assertArrayNotHasKey($field, $senders->first(), "sender.{$field} leaked to student");
        }
    }
}
