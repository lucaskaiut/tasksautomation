<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStageHistory;
use App\Models\User;
use App\Support\Enums\TaskImplementationType;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStage;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'environment_profile_id' => null,
            'created_by' => User::factory(),
            'claimed_by_worker' => null,
            'claimed_at' => null,
            'started_at' => null,
            'finished_at' => null,
            'last_heartbeat_at' => null,
            'attempts' => 0,
            'max_attempts' => 3,
            'locked_until' => null,
            'failure_reason' => null,
            'execution_summary' => null,
            'run_after' => null,
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraphs(2, true),
            'deliverables' => $this->faker->optional()->paragraph(),
            'constraints' => $this->faker->optional()->paragraph(),
            'status' => TaskStatus::Pending->value,
            'priority' => $this->faker->randomElement(array_column(TaskPriority::cases(), 'value')),
            'implementation_type' => $this->faker->randomElement(array_column(TaskImplementationType::cases(), 'value')),
            'current_stage' => TaskStage::Analysis->value,
            'review_status' => null,
            'revision_count' => 0,
            'last_reviewed_at' => null,
            'last_reviewed_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Task $task): void {
            TaskStageHistory::query()->create([
                'task_id' => $task->id,
                'stage' => $task->current_stage,
                'summary' => 'Tarefa criada (factory)',
            ]);
        });
    }
}
