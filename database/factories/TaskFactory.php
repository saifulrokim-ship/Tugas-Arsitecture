<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'parent_id' => null,
            'name' => fake()->words(3, true),
            'start_date' => null,
            'duration' => fake()->numberBetween(1, 5),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'sort_order' => 0,
        ];
    }
}
