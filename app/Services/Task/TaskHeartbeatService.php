<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class TaskHeartbeatService
{
    /**
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function handle(Task $task, string $workerId): Task
    {
        if ($task->claimed_by_worker !== $workerId) {
            throw new AuthorizationException('Esta tarefa foi reservada por outro worker.');
        }

        if (! in_array($task->status, [TaskStatus::Claimed, TaskStatus::Running], true)) {
            throw new ConflictHttpException('A tarefa não está em um estado válido para heartbeat.');
        }

        $now = now();

        $execution = TaskExecution::query()
            ->where('task_id', $task->id)
            ->where('worker_id', $workerId)
            ->whereNull('finished_at')
            ->orderByDesc('id')
            ->first();

        if ($execution === null) {
            throw new ConflictHttpException('Não há execução aberta para esta tarefa.');
        }

        if ($task->status === TaskStatus::Claimed) {
            $task->forceFill([
                'status' => TaskStatus::Running,
                'started_at' => $task->started_at ?? $now,
            ]);

            $execution->forceFill([
                'status' => TaskExecutionStatus::Running,
                'started_at' => $execution->started_at ?? $now,
            ]);
            $execution->save();
        }

        $task->forceFill([
            'last_heartbeat_at' => $now,
            'locked_until' => $now->clone()->addMinutes(10),
        ]);

        $task->save();

        return $task->refresh();
    }
}

