<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Post-audit F-01: must_change_password is enforced server-side. A flagged
 * account may rotate (password/change), onboard, inspect itself and log out —
 * and NOTHING else. Before this middleware only the frontend redirected, so a
 * temp password yielded a fully-capable 7-day token.
 */
class PasswordChangeGateTest extends TestCase
{
    use RefreshDatabase;

    private function flagged(): User
    {
        return User::factory()->create(['must_change_password' => true]);
    }

    public function test_flagged_user_is_blocked_from_the_api_surface(): void
    {
        $this->actingAs($this->flagged())
            ->getJson('/api/v1/specifications')
            ->assertStatus(403)
            ->assertJson(['code' => 'PASSWORD_CHANGE_REQUIRED']);
    }

    public function test_flagged_user_can_still_rotate_inspect_and_logout(): void
    {
        $user = $this->flagged();

        $this->actingAs($user)->getJson('/api/v1/users/me')->assertStatus(200);

        $this->actingAs($user)->postJson('/api/v1/password/change', [
            'old_password' => 'password',
            'new_password' => 'new-secure-password',
            'new_password_confirmation' => 'new-secure-password',
        ])->assertStatus(200);

        $this->assertFalse((bool) $user->fresh()->must_change_password);

        // Rotation lifts the gate.
        $this->actingAs($user)->getJson('/api/v1/specifications')->assertStatus(200);

        $this->actingAs($user)->postJson('/api/v1/auth/logout')->assertStatus(200);
    }

    public function test_unflagged_user_is_unaffected(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/specifications')
            ->assertStatus(200);
    }
}
