<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Round-2 (V2-05): versions share the 5/day upload quota — the bypass that
 * allowed unbounded 50MB/day staged ingestion is closed.
 */
class VersionQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_versions_share_the_daily_upload_quota(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $student = User::factory()->create();
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        // 1) initial upload (claim #1, staged pending)
        $this->actingAs($student)->postJson('/api/v1/resources/upload', [
            'file' => $this->fakePdf(),
            'title' => 'v1',
            'course_id' => $course->id,
            'professor_id' => $prof->id,
        ])->assertStatus(201);
        $parent = Resource::latest('created_at_g')->first();
        $parent->update(['status' => 'approved']); // as an approver would

        // 2) four new versions as the same uploader (claims #2..#5)
        $latestId = $parent->id;
        for ($i = 2; $i <= 5; $i++) {
            $resp = $this->actingAs($student)->postJson("/api/v1/resources/{$parent->id}/new-version", [
                'file' => $this->fakePdf("v{$i}.pdf"),
            ]);
            $resp->assertStatus(201);
        }

        // 3) the sixth claim of the day is refused
        $this->actingAs($student)->postJson("/api/v1/resources/{$parent->id}/new-version", [
            'file' => $this->fakePdf('v6.pdf'),
        ])->assertStatus(429)->assertJson(['code' => 'QUOTA_EXCEEDED']);
    }
}
