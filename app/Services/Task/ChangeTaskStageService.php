<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskStageHistory;
use App\Support\Enums\TaskStage;
use Illuminate\Support\Facades\DB;

final class ChangeTaskStageService
{
    public function handle(Task $task, TaskStage $stage, string $summary): Task
    {
        return DB::transaction(function () use ($task, $stage, $summary): Task {
            TaskStageHistory::query()->create([
                'task_id' => $task->id,
                'stage' => $stage,
                'summary' => $summary,
            ]);

            $task->current_stage = $stage;
            $task->save();

            return $task->refresh();
        });
    }
}
