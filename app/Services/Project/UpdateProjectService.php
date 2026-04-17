<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Support\DTOs\ProjectData;

final class UpdateProjectService
{
    public function handle(Project $project, ProjectData $data): Project
    {
        $payload = [
            'name' => $data->name,
            'description' => $data->description,
            'repository_url' => $data->repositoryUrl,
            'default_branch' => $data->defaultBranch,
            'global_rules' => $data->globalRules,
            'is_active' => $data->isActive,
        ];

        if ($data->slug !== null && $data->slug !== '') {
            $payload['slug'] = $data->slug;
        }

        $project->fill($payload);
        $project->save();

        return $project->refresh();
    }
}

