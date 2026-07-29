<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ResourceUploadQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_exceed_daily_upload_quota()
    {
        $student = User::factory()->create(['role' => 'student']);
        Storage::fake('public');

        for ($i = 0; $i < 5; $i++) {
            $file = UploadedFile::fake()->create("file{$i}.pdf", 500);
            $this->actingAs($student)->postJson('/api/resources/upload', [
                'file' => $file,
                'title' => "Resource {$i}",
                'course_id' => 'CS101',
                'professor_id' => $student->id,
            ]);
        }

        $file = UploadedFile::fake()->create('file6.pdf', 500);
        $response = $this->actingAs($student)->postJson('/api/resources/upload', [
            'file' => $file,
            'title' => 'Resource 6',
            'course_id' => 'CS101',
            'professor_id' => $student->id,
        ]);

        $response->assertStatus(429);
    }
}