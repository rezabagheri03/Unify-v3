<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasswordHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_reuse_recent_passwords()
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('OldPass123!')
        ]);

        // Change password
        $this->actingAs($user)->postJson('/api/password/change', [
            'old_password' => 'OldPass123!',
            'new_password' => 'NewPass123!',
            'new_password_confirmation' => 'NewPass123!'
        ]);

        // Try to reuse old password
        $response = $this->actingAs($user)->postJson('/api/password/change', [
            'old_password' => 'NewPass123!',
            'new_password' => 'OldPass123!',
            'new_password_confirmation' => 'OldPass123!'
        ]);

        $response->assertStatus(400);
    }
}