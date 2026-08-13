<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Resource;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Round-2 (V2-05): the staging janitor — orphans older than 24h are swept,
 * pendings older than 14d are auto-rejected (file deleted, uploader told),
 * fresh pendings are left alone.
 */
class StagingCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function uploadPending(User $student, Course $course, User $prof): Resource
    {
        $this->actingAs($student)->postJson('/api/v1/resources/upload', [
            'file' => $this->fakePdf(),
            'title' => 'staged file',
            'course_id' => $course->id,
            'professor_id' => $prof->id,
        ])->assertStatus(201);

        return Resource::latest('created_at_g')->first();
    }

    public function test_stale_pending_is_auto_rejected_and_file_removed(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = User::factory()->create();
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $res = $this->uploadPending($student, $course, $prof);
        $this->assertEquals('pending', $res->status);
        $temp = $res->temp_path;
        $this->assertNotNull($temp);
        Storage::disk('local')->assertExists($temp);

        $res->update(['created_at_g' => now()->subDays(15)]);

        $this->artisan('files:cleanup-staging')->assertSuccessful();

        $fresh = $res->fresh();
        $this->assertEquals('rejected', $fresh->status);
        $this->assertNull($fresh->temp_path);
        Storage::disk('local')->assertMissing($temp);
        $this->assertDatabaseHas('notifications', ['user_id' => $student->id]);
    }

    public function test_fresh_pending_survives_the_sweep(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = User::factory()->create();
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $res = $this->uploadPending($student, $course, $prof);
        $temp = $res->temp_path;

        $this->artisan('files:cleanup-staging')->assertSuccessful();

        $this->assertEquals('pending', $res->fresh()->status);
        Storage::disk('local')->assertExists($temp);
    }

    public function test_orphan_older_than_24h_is_deleted_newer_is_kept(): void
    {
        Storage::fake('local');
        $old = 'temp/ghost/old.pdf';
        $fresh = 'temp/ghost/fresh.pdf';
        Storage::disk('local')->put($old, 'x');
        Storage::disk('local')->put($fresh, 'x');

        // Age the orphan beyond the 24h safety window.
        touch(Storage::disk('local')->path($old), time() - 90000);

        $this->artisan('files:cleanup-staging')->assertSuccessful();

        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($fresh);
    }
}
