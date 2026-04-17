<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\TaskReview;
use App\Models\User;
use App\Support\Enums\TaskReviewDecision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskReview>
 */
class TaskReviewFactory extends Factory
{
    protected $model = TaskReview::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'task_execution_id' => TaskExecution::factory(),
            'created_by' => User::factory(),
            'decision' => TaskReviewDecision::Approved,
            'notes' => $this->faker->sentence(),
            'current_behavior' => null,
            'expected_behavior' => null,
            'preserve_scope' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (TaskReview $review): void {
            TaskExecution::query()->whereKey($review->task_execution_id)->update([
                'task_id' => $review->task_id,
            ]);
        });
    }
}
