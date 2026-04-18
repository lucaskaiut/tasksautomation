<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ClaimTaskService
{
    public function handle(string $workerId): ?Task
    {
        return DB::transaction(function () use ($workerId): ?Task {
            /** @var Task|null $task */
            $task = Task::query()
                ->eligibleForClaim()
                ->whereHas('project', function (Builder $query): void {
                    $query->where('is_active', true);
                })
                ->orderByRaw(
                    "CASE priority
                        WHEN 'high' THEN 1
                        WHEN 'medium' THEN 2
                        WHEN 'low' THEN 3
                        ELSE 4
                    END"
                )
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if ($task === null) {
                return null;
            }

            $now = now();

            $task->forceFill([
                'status' => TaskStatus::Claimed,
                'claimed_by_worker' => $workerId,
                'claimed_at' => $now,
                'locked_until' => $now->clone()->addMinutes(10),
                'attempts' => $task->attempts + 1,
            ]);

            $task->save();

            TaskExecution::query()->create([
                'task_id' => $task->id,
                'worker_id' => $workerId,
                'status' => TaskExecutionStatus::Claimed,
            ]);

            return $task->refresh();
        });
    }
}
