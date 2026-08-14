<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => (string) $this->faker->unique()->numerify('40########'),
            'password_hash' => Hash::make('password', ['rounds' => 4]),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'role' => 'student',
            'department_id' => 'CS',
            'academic_status_declared' => 'normal',
            'academic_status_declaration_count' => 1,
            'is_honor_system_acknowledged' => true,
            'must_change_password' => false,
        ];
    }

    public function professor(): static
    {
        return $this->state(fn () => ['role' => 'professor', 'department_id' => 'CS']);
    }

    public function expert(): static
    {
        return $this->state(fn () => ['role' => 'expert', 'department_id' => 'CS']);
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => 'owner', 'department_id' => null]);
    }
}
