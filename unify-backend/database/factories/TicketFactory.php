<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Ticket;
use App\Models\User;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'student_id' => User::factory()->create()->id,
            'department' => $this->faker->randomElement(['education', 'technical', 'student_affairs']),
            'subject' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'status' => 'open',
        ];
    }
}
