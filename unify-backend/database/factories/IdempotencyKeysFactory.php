<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\IdempotencyKeys;
use App\Models\User;

class IdempotencyKeysFactory extends Factory
{
    protected $model = IdempotencyKeys::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'key' => (string) Str::uuid(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'response_code' => 200,
            'response_body' => json_encode(['message' => 'ok']),
            'created_at' => now(),
            'expires_at' => now()->addHours(24),
        ];
    }
}
