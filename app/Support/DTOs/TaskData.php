<?php

namespace App\Support\DTOs;

use App\Support\Enums\TaskImplementationType;
use App\Support\Enums\TaskPriority;
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
    ) {}

    /**
     * @param  array<string,mixed>  $validated
     */
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
        );
    }
}
