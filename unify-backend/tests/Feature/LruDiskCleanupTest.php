<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Round-2 (V2-06): LRU cleanup frees bytes on the SECURED disk (post-SEC-05
 * reality), tombstones only on an actual delete, and never nulls file_path of
 * rows whose bytes it could not find.
 */
class LruDiskCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function professorUpload(User $prof, Course $course): Resource
    {
        $this->actingAs($prof)->postJson('/api/v1/resources/upload', [
            'file' => $this->fakePdf(),
            'title' => 'auto-approved',
            'course_id' => $course->id,
        ])->assertStatus(201);

        return Resource::latest('created_at_g')->first();
    }

    public function test_local_disk_file_is_actually_deleted_then_tombstoned(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $res = $this->professorUpload($prof, $course);
        $path = $res->file_path;
        $this->assertEquals('approved', $res->status);
        Storage::disk('local')->assertExists($path);

        config(['unify.lru_limit_bytes' => 1]); // force the cleanup branch

        $this->artisan('files:lru-cleanup')->assertSuccessful();

        $fresh = $res->fresh();
        $this->assertTrue((bool) $fresh->is_deleted_content);
        $this->assertNull($fresh->file_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_row_whose_file_is_missing_is_not_tombstoned(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $res = $this->professorUpload($prof, $course);
        // Simulate bytes that vanished out-of-band (manual host surgery).
        Storage::disk('local')->delete($res->file_path);

        config(['unify.lru_limit_bytes' => 1]);

        $this->artisan('files:lru-cleanup')->assertSuccessful();

        $fresh = $res->fresh();
        $this->assertFalse((bool) $fresh->is_deleted_content);
        $this->assertNotNull($fresh->file_path); // legacy-fallback path preserved
    }
}
