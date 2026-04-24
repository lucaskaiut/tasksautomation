<?php

namespace App\Support\Realtime;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Support\TaskStatusPresenter;
use BackedEnum;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskStreamPayloadFactory
{
    public function __construct(
        private readonly TaskStatusPresenter $taskStatusPresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<int, string>  $changedAttributes
     * @return array<string, mixed>
     */
    public function make(string $type, Task $task, array $previous = [], array $changedAttributes = [], ?CarbonInterface $occurredAt = null): array
    {
        $task->loadMissing(['project', 'environmentProfile', 'lastReviewer', 'creator', 'stageHistories']);

        /** @var array<string, mixed> $taskSnapshot */
        $taskSnapshot = (new TaskResource($task))->toArray(Request::create('/'));
        $status = (string) ($taskSnapshot['status'] ?? $task->status?->value ?? '');
        $reviewStatus = (string) ($taskSnapshot['review_status'] ?? $task->review_status?->value ?? '');
        $statusPresentations = $this->taskStatusPresenter->presentations();
        $reviewPresentation = $this->reviewStatusPresentation($reviewStatus);

        return [
            'type' => $type,
            'task_id' => (int) $task->getKey(),
            'project_id' => (int) $task->project_id,
            'occurred_at' => ($occurredAt ?? $task->updated_at ?? Carbon::now())->toIso8601String(),
            'changes' => $this->formatChanges($taskSnapshot, $previous, $changedAttributes),
            'task' => $taskSnapshot,
            'presentation' => [
                'status' => $statusPresentations[$status] ?? [
                    'label' => $status,
                    'badge_classes' => 'bg-slate-100 text-slate-700',
                ],
                'review_status' => $reviewPresentation,
                'priority' => $task->priority?->value,
                'worker' => $task->claimed_by_worker,
                'attempts' => sprintf('%d / %d', $task->attempts, $task->max_attempts),
                'last_reviewed_at' => $task->last_reviewed_at?->format('d/m/Y H:i'),
                'last_reviewer_name' => $task->lastReviewer?->name,
                'creator_name' => $task->creator?->name,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $taskSnapshot
     * @param  array<string, mixed>  $previous
     * @param  array<int, string>  $changedAttributes
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function formatChanges(array $taskSnapshot, array $previous, array $changedAttributes): array
    {
        $changes = [];

        foreach ($changedAttributes as $attribute) {
            if (! array_key_exists($attribute, $taskSnapshot)) {
                continue;
            }

            $changes[$attribute] = [
                'from' => $this->normalizeValue($previous[$attribute] ?? null),
                'to' => $this->normalizeValue($taskSnapshot[$attribute]),
            ];
        }

        return $changes;
    }

    /**
     * @return array{label: string, badge_classes: string}|null
     */
    private function reviewStatusPresentation(string $reviewStatus): ?array
    {
        if ($reviewStatus === '') {
            return null;
        }

        return match ($reviewStatus) {
            'pending_review' => [
                'label' => 'Aguardando revisão',
                'badge_classes' => 'bg-violet-100 text-violet-800',
            ],
            'approved' => [
                'label' => 'Aprovada',
                'badge_classes' => 'bg-emerald-100 text-emerald-800',
            ],
            'needs_adjustment' => [
                'label' => 'Precisa de ajustes',
                'badge_classes' => 'bg-orange-100 text-orange-800',
            ],
            default => [
                'label' => $reviewStatus,
                'badge_classes' => 'bg-slate-100 text-slate-700',
            ],
        };
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toIso8601String();
        }

        return $value;
    }
}
