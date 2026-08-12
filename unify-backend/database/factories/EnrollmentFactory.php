<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Enrollment;
use App\Models\CourseSpecification;
use App\Models\User;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        // Always create a fresh spec so multiple enrollments never collide
        // on the (student_id, specification_id, semester_id) unique index.
        $spec = CourseSpecification::factory()->create();
        $student = User::where('role', 'student')->first() ?? User::factory()->create();

        return [
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'specification_id' => $spec->id,
            'semester_id' => $spec->semester_id,
            'status' => 'temporary',
            'academic_status_at_enrollment' => 'normal',
            'enrolled_at' => now(),
        ];
    }
}
