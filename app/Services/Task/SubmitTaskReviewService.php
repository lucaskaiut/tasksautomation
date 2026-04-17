<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\TaskReview;
use App\Models\User;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskReviewDecision;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SubmitTaskReviewService
{
    public function handle(
        Task $task,
        TaskExecution $execution,
        User $reviewer,
        TaskReviewDecision $decision,
        string $notes,
        ?string $currentBehavior,
        ?string $expectedBehavior,
        ?string $preserveScope,
    ): TaskReview {
        return DB::transaction(function () use (
            $task,
            $execution,
            $reviewer,
            $decision,
            $notes,
            $currentBehavior,
            $expectedBehavior,
            $preserveScope,
        ): TaskReview {
            $task = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $execution = TaskExecution::query()->whereKey($execution->id)->lockForUpdate()->firstOrFail();

            if ((int) $execution->task_id !== (int) $task->id) {
                throw new ConflictHttpException('A execução não pertence a esta tarefa.');
            }

            if ($task->status !== TaskStatus::Review) {
                throw new ConflictHttpException('A tarefa não está aguardando revisão funcional.');
            }

            if ($execution->status !== TaskExecutionStatus::Review) {
                throw new ConflictHttpException('Esta execução não está disponível para revisão.');
            }

            if ($execution->finished_at === null) {
                throw new ConflictHttpException('A execução ainda não foi finalizada tecnicamente.');
            }

            if ($execution->review()->exists()) {
                throw new ConflictHttpException('Esta execução já possui revisão registrada.');
            }

            $now = now();

            $review = TaskReview::query()->create([
                'task_id' => $task->id,
                'task_execution_id' => $execution->id,
                'created_by' => $reviewer->id,
                'decision' => $decision,
                'notes' => $notes,
                'current_behavior' => $currentBehavior,
                'expected_behavior' => $expectedBehavior,
                'preserve_scope' => $preserveScope,
            ]);

            if ($decision === TaskReviewDecision::Approved) {
                $task->forceFill([
                    'status' => TaskStatus::Done,
                    'review_status' => TaskReviewStatus::Approved,
                    'last_reviewed_at' => $now,
                    'last_reviewed_by' => $reviewer->id,
                ]);
                $task->save();

                $execution->forceFill([
                    'status' => TaskExecutionStatus::Done,
                ]);
                $execution->save();

                return $review->refresh();
            }

            $task->forceFill([
                'status' => TaskStatus::Pending,
                'review_status' => TaskReviewStatus::NeedsAdjustment,
                'revision_count' => $task->revision_count + 1,
                'last_reviewed_at' => $now,
                'last_reviewed_by' => $reviewer->id,
                'claimed_by_worker' => null,
                'claimed_at' => null,
                'started_at' => null,
                'finished_at' => null,
                'last_heartbeat_at' => null,
                'locked_until' => null,
                'execution_summary' => null,
                'failure_reason' => null,
            ]);
            $task->save();

            $execution->forceFill([
                'status' => TaskExecutionStatus::Done,
            ]);
            $execution->save();

            return $review->refresh();
        });
    }
}
