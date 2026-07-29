<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_returns_ok()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }
}