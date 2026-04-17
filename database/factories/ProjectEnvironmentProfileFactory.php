<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectEnvironmentProfile>
 */
class ProjectEnvironmentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->optional()->sentence(10),
            'validation_profile' => $this->faker->optional()->randomElement([
                ['strict' => true],
                ['strict' => false, 'rules' => ['max_files' => 10]],
            ]),
            'environment_definition' => $this->faker->optional()->randomElement([
                ['runtime' => 'php', 'version' => '8.4'],
                ['runtime' => 'node', 'version' => '22'],
            ]),
            'docker_compose_yml' => $this->faker->optional()->randomElement([
                "services:\n  app:\n    image: example/app\n",
                "services:\n  db:\n    image: mysql:8\n",
            ]),
            'is_default' => false,
        ];
    }
}
