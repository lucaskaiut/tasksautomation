<?php

namespace App\Support\Realtime;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Support\TaskStatusPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\CarbonInterface;

final class TaskStatusPayloadFactory
{
    public function __construct(
        private readonly TaskStatusPresenter $taskStatusPresenter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function make(Task $task, ?string $previousStatus, CarbonInterface $changedAt): array
    {
        $task->loadMissing(['project', 'environmentProfile', 'lastReviewer']);

        /** @var array<string, mixed> $taskSnapshot */
        $taskSnapshot = (new TaskResource($task))->toArray(Request::create('/'));

        $presentations = $this->taskStatusPresenter->presentations();
        $status = (string) ($taskSnapshot['status'] ?? $task->status?->value ?? '');

        return [
            'type' => 'task.status.changed',
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'previous_status' => $previousStatus,
            'status' => $status,
            'changed_at' => $changedAt->toIso8601String(),
            'task' => $taskSnapshot,
            'presentation' => [
                'status' => $presentations[$status] ?? [
                    'label' => $status,
                    'badge_classes' => 'bg-slate-100 text-slate-700',
                ],
                'review_status' => $task->review_status?->value ?? '—',
                'priority' => $task->priority?->value,
                'worker' => $task->claimed_by_worker,
                'attempts' => sprintf('%d / %d', $task->attempts, $task->max_attempts),
                'last_reviewed_at' => $task->last_reviewed_at?->format('d/m/Y H:i'),
                'last_reviewer_name' => $task->lastReviewer?->name,
            ],
        ];
    }
}
