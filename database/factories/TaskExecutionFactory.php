<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Support\Enums\TaskExecutionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskExecution>
 */
class TaskExecutionFactory extends Factory
{
    protected $model = TaskExecution::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'worker_id' => 'worker-'.$this->faker->uuid(),
            'status' => TaskExecutionStatus::Claimed,
            'started_at' => null,
            'finished_at' => null,
            'summary' => null,
            'failure_reason' => null,
            'logs_path' => null,
            'branch_name' => null,
            'commit_sha' => null,
            'pull_request_url' => null,
            'metadata' => null,
        ];
    }
}
