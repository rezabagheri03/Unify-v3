<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_own_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/users/me');

        $response->assertStatus(200)
                 ->assertJson(['id' => $user->id]);
    }

    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('OldPass123!')
        ]);

        $response = $this->actingAs($user)->postJson('/api/password/change', [
            'old_password' => 'OldPass123!',
            'new_password' => 'NewPass123!',
            'new_password_confirmation' => 'NewPass123!'
        ]);

        $response->assertStatus(200);
    }

    public function test_user_cannot_reuse_old_password()
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('OldPass123!')
        ]);

        $this->actingAs($user)->postJson('/api/password/change', [
            'old_password' => 'OldPass123!',
            'new_password' => 'NewPass123!',
            'new_password_confirmation' => 'NewPass123!'
        ]);

        $response = $this->actingAs($user)->postJson('/api/password/change', [
            'old_password' => 'NewPass123!',
            'new_password' => 'OldPass123!',
            'new_password_confirmation' => 'OldPass123!'
        ]);

        $response->assertStatus(400);
    }
}