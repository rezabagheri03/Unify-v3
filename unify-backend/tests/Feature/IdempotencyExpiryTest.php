<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\IdempotencyKeys;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Round-2 (V2-17e): an UNEXPIRED key replays the first response verbatim;
 * an EXPIRED key is reprocessed (and freed) instead of replaying a stale win.
 */
class IdempotencyExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function add(User $student, CourseSpecification $spec, string $key)
    {
        return $this->actingAs($student)
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/enrollment/temp', ['specification_id' => $spec->id]);
    }

    public function test_live_key_replays_the_same_response(): void
    {
        $student = User::factory()->create();
        $spec = CourseSpecification::factory()->create();

        $first = $this->add($student, $spec, 'key-live-1')->assertStatus(201);
        $firstId = $first->json('enrollment.id');

        $second = $this->add($student, $spec, 'key-live-1')->assertStatus(201);
        $this->assertEquals($firstId, $second->json('enrollment.id'));
        $this->assertEquals(1, Enrollment::where('student_id', $student->id)->count());
    }

    public function test_expired_key_is_reprocessed_as_a_fresh_intent(): void
    {
        $student = User::factory()->create();
        $spec = CourseSpecification::factory()->create();

        $first = $this->add($student, $spec, 'key-dead-1')->assertStatus(201);
        $firstId = $first->json('enrollment.id');

        // Remove the temp row, then expire the key — as if grace wiped the
        // list and the purge cron has not swept the key yet.
        $this->actingAs($student)->deleteJson('/api/v1/enrollment/temp/' . $firstId)->assertStatus(200);
        IdempotencyKeys::where('key', 'key-dead-1')->update(['expires_at' => now()->subMinute()]);

        $second = $this->add($student, $spec, 'key-dead-1')->assertStatus(201);
        $secondId = $second->json('enrollment.id');

        $this->assertNotEquals($firstId, $secondId); // truly reprocessed
        $this->assertEquals(1, Enrollment::where('student_id', $student->id)->where('status', 'temporary')->count());
        $this->assertEquals('201', (string) IdempotencyKeys::where('key', 'key-dead-1')->value('response_code'));
    }
}
