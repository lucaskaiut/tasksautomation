<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(3),
            'description' => $this->faker->optional()->sentence(12),
            'repository_url' => $this->faker->url(),
            'default_branch' => 'main',
            'global_rules' => $this->faker->optional()->randomElement([
                ['notes' => $this->faker->sentence()],
                ['constraints' => [$this->faker->word(), $this->faker->word()]],
            ]),
            'is_active' => true,
        ];
    }
}
