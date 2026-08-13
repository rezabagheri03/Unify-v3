<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnrollmentOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_add_overlapping_specifications()
    {
        $student = User::factory()->create(['role' => 'student']);
        
        $spec1 = CourseSpecification::factory()->create([
            'day_of_week' => 'sat',
            'time_start' => '08:00',
            'time_end' => '10:00'
        ]);
        
        $spec2 = CourseSpecification::factory()->create([
            'day_of_week' => 'sat',
            'time_start' => '09:00',
            'time_end' => '11:00'
        ]);

        $this->actingAs($student)->postJson('/api/v1/enrollment/temp', [
            'specification_id' => $spec1->id
        ]);

        $response = $this->actingAs($student)->postJson('/api/v1/enrollment/temp', [
            'specification_id' => $spec2->id
        ]);

        $response->assertStatus(409);
    }
}