<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_logo()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/branding/logo', [
            'logo' => $file
        ]);

        $response->assertStatus(200);
    }
}