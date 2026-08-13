<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseSpecification;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * TODO-041 product rule: a student may enroll in ONE section per
 * (course, professor) within a semester — "Calculus 1 with Prof. Ahmadi
 * only once per term". Retakes in a LATER semester are allowed.
 *
 * TestCase::setUp seeds the current semester (1403-1, state=enrolling) and
 * the CS department; UserFactory declares honor status by default.
 */
class EnrollmentCourseProfUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private function specFor(string $courseId, string $profId, string $day): CourseSpecification
    {
        return CourseSpecification::factory()->create([
            'course_id' => $courseId,
            'professor_id' => $profId,
            'day_of_week' => $day, // different days avoid the TIME_OVERLAP guard
        ]);
    }

    public function test_second_section_same_course_same_professor_is_blocked(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $ahmadi = User::factory()->professor()->create();
        $calculus = Course::factory()->create(['name' => 'ریاضی ۱']);

        $sectionA = $this->specFor($calculus->id, $ahmadi->id, 'sat');
        $sectionB = $this->specFor($calculus->id, $ahmadi->id, 'sun');

        $this->actingAs($student)
            ->postJson('/api/v1/enrollment/temp', ['specification_id' => $sectionA->id])
            ->assertStatus(201);

        $this->actingAs($student)
            ->postJson('/api/v1/enrollment/temp', ['specification_id' => $sectionB->id])
            ->assertStatus(409)
            ->assertJsonPath('code', 'COURSE_PROF_DUPLICATE');
    }

    public function test_same_course_with_different_professor_is_allowed(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $ahmadi = User::factory()->professor()->create();
        $rezaei = User::factory()->professor()->create();
        $calculus = Course::factory()->create(['name' => 'ریاضی ۱']);

        $withAhmadi = $this->specFor($calculus->id, $ahmadi->id, 'sat');
        $withRezaei = $this->specFor($calculus->id, $rezaei->id, 'sun');

        $this->actingAs($student)
            ->postJson('/api/v1/enrollment/temp', ['specification_id' => $withAhmadi->id])
            ->assertStatus(201);

        $this->actingAs($student)
            ->postJson('/api/v1/enrollment/temp', ['specification_id' => $withRezaei->id])
            ->assertStatus(201);
    }

    public function test_block_is_semester_scoped(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $ahmadi = User::factory()->professor()->create();
        $calculus = Course::factory()->create();
        $pastSemester = \App\Models\Semester::factory()->create([
            'id' => '1402-2',
            'is_current' => false,
            'global_state' => 'exam',
        ]);

        // An enrollment in a PAST semester for the same (course, professor)
        // must not block a new-term enrollment (fail/improve retakes).
        $pastSpec = CourseSpecification::factory()->create([
            'course_id' => $calculus->id,
            'professor_id' => $ahmadi->id,
            'semester_id' => $pastSemester->id,
        ]);
        \App\Models\Enrollment::factory()->create([
            'student_id' => $student->id,
            'specification_id' => $pastSpec->id,
            'semester_id' => $pastSemester->id,
            'status' => 'finalized',
        ]);

        $newSection = $this->specFor($calculus->id, $ahmadi->id, 'sun');

        $this->actingAs($student)
            ->postJson('/api/v1/enrollment/temp', ['specification_id' => $newSection->id])
            ->assertStatus(201);
    }
}
