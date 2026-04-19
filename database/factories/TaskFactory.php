<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
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
            'analysis_domain' => null,
            'analysis_confidence' => null,
            'analysis_next_stage' => null,
            'analysis_summary' => null,
            'analysis_evidence' => null,
            'analysis_risks' => null,
            'analysis_artifacts' => null,
            'analysis_notes' => null,
            'stage_execution_reference' => null,
            'stage_execution_stage' => null,
            'stage_execution_status' => null,
            'stage_execution_agent' => null,
            'stage_execution_summary' => null,
            'stage_execution_output' => null,
            'stage_execution_raw_output' => null,
            'stage_execution_exit_code' => null,
            'stage_execution_started_at' => null,
            'stage_execution_finished_at' => null,
            'stage_execution_context' => null,
            'handoff_from_stage' => null,
            'handoff_to_stage' => null,
            'handoff_reason' => null,
            'handoff_confidence' => null,
            'handoff_summary' => null,
            'handoff_payload' => null,
            'review_status' => null,
            'revision_count' => 0,
            'last_reviewed_at' => null,
            'last_reviewed_by' => null,
        ];
    }
}
