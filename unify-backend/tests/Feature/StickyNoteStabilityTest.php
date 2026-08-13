<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Round-2 (V2-16): saving your sticky note twice keeps ONE row with ONE id —
 * the old updateOrCreate-with-id churned the primary key every save.
 */
class StickyNoteStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_resaving_a_note_does_not_churn_its_primary_key(): void
    {
        Storage::fake('local');
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();
        $this->actingAs($prof)->postJson('/api/v1/resources/upload', [
            'file' => $this->fakePdf(), 'title' => 't', 'course_id' => $course->id,
        ])->assertStatus(201);
        $resource = Resource::latest('created_at_g')->first();

        $student = User::factory()->create();
        $this->actingAs($student)->postJson("/api/v1/resources/{$resource->id}/sticky-note", ['note' => 'یادداشت اول'])->assertStatus(200);

        $first = \App\Models\ResourceStickyNote::where('student_id', $student->id)
            ->where('resource_family_id', $resource->family_id)->first();
        $this->assertNotNull($first);

        $this->actingAs($student)->postJson("/api/v1/resources/{$resource->id}/sticky-note", ['note' => 'ویرایش'])->assertStatus(200);

        $rows = \App\Models\ResourceStickyNote::where('student_id', $student->id)
            ->where('resource_family_id', $resource->family_id)->get();
        $this->assertCount(1, $rows);
        $this->assertEquals($first->id, $rows->first()->id);
    }
}
