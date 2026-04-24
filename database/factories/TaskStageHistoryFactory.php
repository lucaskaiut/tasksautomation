<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskStageHistory;
use App\Support\Enums\TaskStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskStageHistory>
 */
class TaskStageHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'stage' => TaskStage::Analysis->value,
            'summary' => $this->faker->sentence(),
        ];
    }
}
