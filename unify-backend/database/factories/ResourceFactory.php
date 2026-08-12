<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Course;
use App\Models\Resource;
use App\Models\User;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        $course = Course::inRandomOrder()->first() ?? Course::factory()->create();
        $professor = User::where('role', 'professor')->first() ?? User::factory()->professor()->create();

        return [
            'id' => (string) Str::uuid(),
            'course_id' => $course->id,
            'professor_id' => $professor->id,
            'uploader_id' => $professor->id,
            'title' => $this->faker->sentence(3),
            'file_path' => 'resources/' . $course->id . '/' . $professor->id . '/' . Str::uuid() . '.pdf',
            'file_size_bytes' => 2048,
            'file_mime' => 'application/pdf',
            'created_at_g' => now(),
            'status' => 'approved',
            'badge_type' => 'professor',
            'version' => 1,
            'family_id' => null, // Observer sets family_id = id (C1)
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'badge_type' => null]);
    }
}
