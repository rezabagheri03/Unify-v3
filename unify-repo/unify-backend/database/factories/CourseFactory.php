<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Course;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'id' => 'CS' . $this->faker->unique()->numberBetween(100, 999),
            'code' => 'CS' . $this->faker->unique()->numberBetween(100, 999),
            'name' => $this->faker->words(3, true),
            'credits' => 3,
            'department_id' => 'CS',
            'is_active' => true,
        ];
    }
}
