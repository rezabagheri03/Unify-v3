<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_import_users()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Storage::fake('local');

        $file = UploadedFile::fake()->create('users.xlsx', 100);

        $response = $this->actingAs($owner)->postJson('/api/owner/users/bulk-import', [
            'file' => $file
        ]);

        $response->assertStatus(200);
    }
}