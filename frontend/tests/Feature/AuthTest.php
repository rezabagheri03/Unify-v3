<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => $user->id,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'access_token']);
    }

    public function test_user_can_complete_onboarding()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/onboarding', [
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi'
        ]);

        $response->assertStatus(200);
    }
}