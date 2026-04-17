<?php

namespace App\Services\Task;

use App\Models\ProjectEnvironmentProfile;
use App\Models\Task;
use App\Models\User;
use App\Support\DTOs\TaskData;
use Illuminate\Validation\ValidationException;

final class CreateTaskService
{
    public function handle(TaskData $data, User $creator): Task
    {
        $this->assertEnvironmentProfileBelongsToProject($data->projectId, $data->environmentProfileId);

        return Task::create([
            'project_id' => $data->projectId,
            'environment_profile_id' => $data->environmentProfileId,
            'created_by' => $creator->id,
            'title' => $data->title,
            'description' => $data->description,
            'deliverables' => $data->deliverables,
            'constraints' => $data->constraints,
            'status' => $data->status,
            'priority' => $data->priority,
        ]);
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

