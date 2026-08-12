<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Course;
use App\Models\CourseSpecification;
use App\Models\User;

class CourseSpecificationFactory extends Factory
{
    protected $model = CourseSpecification::class;

    public function definition(): array
    {
        $course = Course::inRandomOrder()->first() ?? Course::factory()->create();
        $professor = User::where('role', 'professor')->first() ?? User::factory()->professor()->create();

        return [
            'id' => (string) Str::uuid(),
            'course_id' => $course->id,
            'professor_id' => $professor->id,
            'day_of_week' => $this->faker->randomElement(['sat', 'sun', 'mon', 'tue', 'wed']),
            'time_start' => $this->faker->randomElement(['08:00', '10:00', '13:00']),
            'time_end' => $this->faker->randomElement(['10:00', '12:00', '15:00']),
            'location' => 'کلاس ' . $this->faker->numberBetween(101, 199),
            'semester_id' => '1403-1',
            'is_active' => true,
            'is_next_day' => false,
        ];
    }
}
