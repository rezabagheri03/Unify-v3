<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Post-audit F-15: a device can be DEactivated (logout flow), scoped to the
 * caller — you can never kill another account's device row.
 */
class DeviceUnregisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_deactivate_own_device(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/devices', [
            'token' => 'tok-1', 'provider' => 'pushe', 'platform' => 'web',
        ])->assertStatus(200);

        $this->actingAs($user)->deleteJson('/api/v1/devices', ['token' => 'tok-1'])
            ->assertStatus(200);

        $this->assertFalse((bool) DeviceToken::where('token', 'tok-1')->value('is_active'));
    }

    public function test_deactivation_is_scoped_to_the_caller(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->actingAs($a)->postJson('/api/v1/devices', ['token' => 'shared-tok', 'provider' => 'pushe', 'platform' => 'web']);
        $this->actingAs($b)->postJson('/api/v1/devices', ['token' => 'shared-tok', 'provider' => 'pushe', 'platform' => 'android']);

        $this->actingAs($a)->deleteJson('/api/v1/devices', ['token' => 'shared-tok'])->assertStatus(200);

        $this->assertFalse((bool) DeviceToken::where('user_id', $a->id)->where('token', 'shared-tok')->value('is_active'));
        $this->assertTrue((bool) DeviceToken::where('user_id', $b->id)->where('token', 'shared-tok')->value('is_active'));
    }
}
