<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Round-2 (V2-02, product decision: build expert lanes). Staff own FUNCTIONAL
 * lanes on users.ticket_lane; experts/heads see and handle only their lane;
 * admin/owner work everything; lane-less staff see an explicitly empty inbox.
 */
class TicketLaneTest extends TestCase
{
    use RefreshDatabase;

    private function expert(string $role, ?string $lane): User
    {
        return User::factory()->create(['role' => $role, 'ticket_lane' => $lane]);
    }

    public function test_expert_inbox_shows_only_own_lane_tickets(): void
    {
        $edu = $this->expert('expert', 'education');
        $tEdu = Ticket::factory()->create(['department' => 'education']);
        $tTech = Ticket::factory()->create(['department' => 'technical']);

        $resp = $this->actingAs($edu)->getJson('/api/v1/tickets')->assertStatus(200);
        $ids = collect($resp->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($tEdu->id));
        $this->assertFalse($ids->contains($tTech->id));
    }

    public function test_lane_less_expert_sees_explicitly_empty_inbox(): void
    {
        $expert = $this->expert('expert', null);
        Ticket::factory()->create(['department' => 'education']);

        $resp = $this->actingAs($expert)->getJson('/api/v1/tickets')->assertStatus(200);
        $this->assertCount(0, $resp->json('data'));
    }

    public function test_cross_lane_show_reply_and_status_are_forbidden(): void
    {
        $edu = $this->expert('expert', 'education');
        $tTech = Ticket::factory()->create(['department' => 'technical']);

        $this->actingAs($edu)->getJson('/api/v1/tickets/' . $tTech->id)->assertStatus(403);
        $this->actingAs($edu)->postJson('/api/v1/tickets/' . $tTech->id . '/reply', ['body' => 'hi'])->assertStatus(403);
        $this->actingAs($edu)->patchJson('/api/v1/tickets/' . $tTech->id . '/status', ['status' => 'in_progress'])->assertStatus(403);
    }

    public function test_in_lane_reply_and_status_work(): void
    {
        $edu = $this->expert('expert', 'education');
        $tEdu = Ticket::factory()->create(['department' => 'education']);

        $this->actingAs($edu)->postJson('/api/v1/tickets/' . $tEdu->id . '/reply', ['body' => 'پاسخ'])->assertStatus(201);
        $this->actingAs($edu)->patchJson('/api/v1/tickets/' . $tEdu->id . '/status', ['status' => 'in_progress'])->assertStatus(200);
        $this->assertDatabaseHas('tickets', ['id' => $tEdu->id, 'status' => 'in_progress']);
    }

    public function test_admin_sees_and_handles_every_lane(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tEdu = Ticket::factory()->create(['department' => 'education']);
        $tTech = Ticket::factory()->create(['department' => 'technical']);

        $resp = $this->actingAs($admin)->getJson('/api/v1/tickets')->assertStatus(200);
        $this->assertCount(2, $resp->json('data'));
        $this->actingAs($admin)->getJson('/api/v1/tickets/' . $tTech->id)->assertStatus(200);
        $this->actingAs($admin)->patchJson('/api/v1/tickets/' . $tEdu->id . '/status', ['status' => 'closed'])->assertStatus(200);
    }
}
