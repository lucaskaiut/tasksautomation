<?php

namespace App\Services\Task;

use App\Models\ProjectEnvironmentProfile;
use App\Models\Task;
use App\Services\Realtime\TaskStatusStreamPublisher;
use App\Support\DTOs\TaskData;
use Illuminate\Validation\ValidationException;

final class UpdateTaskService
{
    public function __construct(
        private readonly TaskStatusStreamPublisher $taskStatusStreamPublisher,
    ) {
    }

    public function handle(Task $task, TaskData $data): Task
    {
        $this->assertEnvironmentProfileBelongsToProject($data->projectId, $data->environmentProfileId);
        $previousStatus = $task->status?->value ?? (string) $task->status;

        $task->fill([
            'project_id' => $data->projectId,
            'environment_profile_id' => $data->environmentProfileId,
            'title' => $data->title,
            'description' => $data->description,
            'deliverables' => $data->deliverables,
            'constraints' => $data->constraints,
            'status' => $data->status,
            'priority' => $data->priority,
            'implementation_type' => $data->implementationType,
        ]);

        $task->save();

        $task = $task->refresh();

        if (($task->status?->value ?? (string) $task->status) !== $previousStatus) {
            $this->taskStatusStreamPublisher->publishStatusChange($task, $previousStatus);
        }

        return $task;
    }

    private function assertEnvironmentProfileBelongsToProject(int $projectId, ?int $environmentProfileId): void
    {
        if ($environmentProfileId === null) {
            return;
        }

        $profile = ProjectEnvironmentProfile::query()->find($environmentProfileId);
        if ($profile === null || $profile->project_id !== $projectId) {
            throw ValidationException::withMessages([
                'environment_profile_id' => ['O perfil de ambiente deve pertencer ao mesmo projeto da tarefa.'],
            ]);
        }
    }
}
