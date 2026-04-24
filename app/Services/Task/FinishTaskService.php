<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class FinishTaskService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     *
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function handle(
        Task $task,
        string $workerId,
        TaskStatus $requestedStatus,
        ?string $executionSummary,
        ?string $failureReason,
        ?string $branchName = null,
        ?string $commitSha = null,
        ?string $pullRequestUrl = null,
        ?string $logsPath = null,
        ?array $metadata = null,
    ): Task {
        return DB::transaction(function () use (
            $task,
            $workerId,
            $requestedStatus,
            $executionSummary,
            $failureReason,
            $branchName,
            $commitSha,
            $pullRequestUrl,
            $logsPath,
            $metadata,
        ): Task {
            $task = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($task->claimed_by_worker !== $workerId) {
                throw new AuthorizationException('Esta tarefa foi reservada por outro worker.');
            }

            if (! in_array($task->status, [TaskStatus::Claimed, TaskStatus::Running], true)) {
                throw new ConflictHttpException('A tarefa não está em um estado válido para finalização.');
            }

            $execution = TaskExecution::query()
                ->where('task_id', $task->id)
                ->where('worker_id', $workerId)
                ->whereNull('finished_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($execution === null) {
                throw new ConflictHttpException('Não há execução aberta para esta tarefa.');
            }

            $now = Carbon::now();

            $executionFill = [
                'branch_name' => $branchName,
                'commit_sha' => $commitSha,
                'pull_request_url' => $pullRequestUrl,
                'logs_path' => $logsPath,
                'metadata' => $metadata,
                'finished_at' => $now,
            ];

            if ($requestedStatus === TaskStatus::Pending) {
                $task->forceFill([
                    'status' => TaskStatus::Pending,
                    'review_status' => null,
                    'claimed_by_worker' => null,
                    'claimed_at' => null,
                    'started_at' => null,
                    'finished_at' => null,
                    'last_heartbeat_at' => null,
                    'locked_until' => null,
                    'failure_reason' => null,
                    'execution_summary' => $executionSummary,
                ]);
                $task->save();

                $execution->forceFill(array_merge($executionFill, [
                    'status' => TaskExecutionStatus::Done,
                    'summary' => $executionSummary,
                    'failure_reason' => null,
                ]));
                $execution->save();

                return $task->refresh();
            }

            if ($requestedStatus === TaskStatus::Done || $requestedStatus === TaskStatus::Review) {
                $task->forceFill([
                    'status' => TaskStatus::Review,
                    'review_status' => TaskReviewStatus::PendingReview,
                    'finished_at' => $now,
                    'execution_summary' => $executionSummary,
                    'failure_reason' => null,
                    'locked_until' => null,
                ]);
                $task->save();

                $execution->forceFill(array_merge($executionFill, [
                    'status' => TaskExecutionStatus::Review,
                    'summary' => $executionSummary,
                    'failure_reason' => null,
                ]));
                $execution->save();

                return $task->refresh();
            }

            if ($requestedStatus === TaskStatus::Failed) {
                $task->forceFill([
                    'status' => TaskStatus::Failed,
                    'review_status' => null,
                    'finished_at' => $now,
                    'execution_summary' => $executionSummary,
                    'failure_reason' => $failureReason,
                    'locked_until' => null,
                ]);
                $task->save();

                $execution->forceFill(array_merge($executionFill, [
                    'status' => TaskExecutionStatus::Failed,
                    'summary' => $executionSummary,
                    'failure_reason' => $failureReason,
                ]));
                $execution->save();

                return $task->refresh();
            }

            if ($requestedStatus === TaskStatus::Blocked) {
                $task->forceFill([
                    'status' => TaskStatus::Blocked,
                    'review_status' => null,
                    'finished_at' => $now,
                    'execution_summary' => $executionSummary,
                    'failure_reason' => $failureReason,
                    'locked_until' => null,
                ]);
                $task->save();

                $execution->forceFill(array_merge($executionFill, [
                    'status' => TaskExecutionStatus::Blocked,
                    'summary' => $executionSummary,
                    'failure_reason' => $failureReason,
                ]));
                $execution->save();

                return $task->refresh();
            }

            if ($requestedStatus === TaskStatus::Cancelled) {
                $task->forceFill([
                    'status' => TaskStatus::Cancelled,
                    'review_status' => null,
                    'finished_at' => $now,
                    'execution_summary' => $executionSummary,
                    'failure_reason' => $failureReason,
                    'locked_until' => null,
                ]);
                $task->save();

                $execution->forceFill(array_merge($executionFill, [
                    'status' => TaskExecutionStatus::Cancelled,
                    'summary' => $executionSummary,
                    'failure_reason' => $failureReason,
                ]));
                $execution->save();

                return $task->refresh();
            }

            throw new ConflictHttpException('Status de finalização não suportado.');
        });
    }
}
