<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Round-2 (V2-14): ratings that arrive via /offline/sync recompute the family
 * score on the head row — identical to the online endpoint's behavior.
 */
class RatingRecalcSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_synced_rating_updates_average_immediately(): void
    {
        Storage::fake('local');
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        // Professor upload = auto-approved head row (family_id = id via observer).
        $this->actingAs($prof)->postJson('/api/v1/resources/upload', [
            'file' => $this->fakePdf(),
            'title' => 'recalc target',
            'course_id' => $course->id,
        ])->assertStatus(201);
        $resource = Resource::latest('created_at_g')->first();

        $student = User::factory()->create();
        $this->actingAs($student)->postJson('/api/v1/offline/sync', [
            'items' => [[
                'type' => 'rating',
                'payload' => ['resource_family_id' => $resource->family_id, 'rating' => 5],
                'idempotency_key' => (string) Str::uuid(),
            ]],
        ])->assertStatus(200)->assertJsonPath('results.0.status', 'synced');

        $fresh = $resource->fresh();
        $this->assertEquals(5.0, (float) $fresh->average_rating);
        $this->assertEquals(1, $fresh->rating_count);
    }
}
