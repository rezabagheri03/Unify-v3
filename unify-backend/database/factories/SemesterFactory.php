<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Semester;

class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        return [
            'id' => '1403-' . $this->faker->unique()->numberBetween(1, 99),
            'name' => 'نیم‌سال اول ۱۴۰۳',
            'is_current' => true,
            'global_state' => 'enrolling',
            'start_date_g' => '2024-09-20 08:00:00',
            'end_date_g' => '2025-01-20 18:00:00',
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => ['is_current' => true]);
    }
}
