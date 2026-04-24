<?php

namespace App\Support\DTOs;

use App\Models\Task;
use App\Support\Enums\TaskImplementationType;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStage;
use App\Support\Enums\TaskStatus;

final readonly class TaskData
{
    public function __construct(
        public int $projectId,
        public ?int $environmentProfileId,
        public string $title,
        public string $description,
        public ?string $deliverables,
        public ?string $constraints,
        public TaskStatus $status,
        public TaskPriority $priority,
        public TaskImplementationType $implementationType,
        public TaskStage $currentStage,
    ) {}

    /**
     * @param  array<string,mixed>  $validated
     */
    public static function forPartialUpdate(Task $task, array $validated): self
    {
        return self::fromValidated(array_merge(self::baselinePayloadFromTask($task), $validated));
    }

    public static function fromValidated(array $validated): self
    {
        return new self(
            projectId: (int) $validated['project_id'],
            environmentProfileId: array_key_exists('environment_profile_id', $validated) && $validated['environment_profile_id'] !== null
                ? (int) $validated['environment_profile_id']
                : null,
            title: $validated['title'],
            description: $validated['description'],
            deliverables: $validated['deliverables'] ?? null,
            constraints: $validated['constraints'] ?? null,
            status: TaskStatus::from($validated['status'] ?? TaskStatus::Pending->value),
            priority: TaskPriority::from($validated['priority']),
            implementationType: TaskImplementationType::from($validated['implementation_type']),
            currentStage: TaskStage::from($validated['current_stage']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function baselinePayloadFromTask(Task $task): array
    {
        return [
            'project_id' => $task->project_id,
            'environment_profile_id' => $task->environment_profile_id,
            'title' => $task->title,
            'description' => $task->description,
            'deliverables' => $task->deliverables,
            'constraints' => $task->constraints,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'implementation_type' => $task->implementation_type->value,
            'current_stage' => $task->current_stage->value,
        ];
    }
}
