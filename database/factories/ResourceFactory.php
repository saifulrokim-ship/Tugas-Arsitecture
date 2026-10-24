<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->name(),
            'contact' => fake()->phoneNumber(),
            'role' => fake()->randomElement(['Frontend Developer', 'Backend Developer', 'UI/UX Designer', 'QA Engineer']),
            'salary' => fake()->numberBetween(5, 15) * 1_000_000,
        ];
    }
}
