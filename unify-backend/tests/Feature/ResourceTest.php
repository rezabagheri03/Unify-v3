<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_upload_resource()
    {
        $student = User::factory()->create(['role' => 'student', 'academic_status_declared' => 'normal']);
        $course = \App\Models\Course::factory()->create();
        $professor = User::factory()->professor()->create();
        Storage::fake('public');

        $response = $this->actingAs($student)->postJson('/api/v1/resources/upload', [
            'file' => $this->fakePdf(),
            'title' => 'Test Resource',
            'course_id' => $course->id,
            'professor_id' => $professor->id,
        ]);

        $response->assertStatus(201);
    }

    public function test_student_cannot_exceed_daily_quota()
    {
        $student = User::factory()->create(['role' => 'student', 'academic_status_declared' => 'normal']);
        $course = \App\Models\Course::factory()->create();
        $professor = User::factory()->professor()->create();
        Storage::fake('public');

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($student)->postJson('/api/v1/resources/upload', [
                'file' => $this->fakePdf("file{$i}.pdf"),
                'title' => "Resource {$i}",
                'course_id' => $course->id,
                'professor_id' => $professor->id,
            ])->assertStatus(201);
        }

        $response = $this->actingAs($student)->postJson('/api/v1/resources/upload', [
            'file' => $this->fakePdf('file6.pdf'),
            'title' => 'Resource 6',
            'course_id' => $course->id,
            'professor_id' => $professor->id,
        ]);

        $response->assertStatus(429);
    }
}