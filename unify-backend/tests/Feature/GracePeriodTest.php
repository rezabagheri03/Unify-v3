<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Semester;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GracePeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_grace_period_wipe_works()
    {
        // The current semester (seeded by TestCase) has an expired grace period.
        Semester::where('id', '1403-1')->update([
            'grace_period_ends_at' => now()->subHour(),
            'grace_period_handled' => false,
        ]);

        Enrollment::factory()->count(4)->create([
            'semester_id' => '1403-1',
            'status' => 'temporary',
        ]);

        $this->artisan('enrollments:wipe-grace');

        $this->assertDatabaseCount('enrollments', 0);
    }
}