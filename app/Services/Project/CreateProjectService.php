<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Support\DTOs\ProjectData;
use Illuminate\Support\Str;

final class CreateProjectService
{
    public function handle(ProjectData $data): Project
    {
        $slug = $data->slug !== null && $data->slug !== '' ? $data->slug : $this->generateUniqueSlug($data->name);

        return Project::create([
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
            'repository_url' => $data->repositoryUrl,
            'default_branch' => $data->defaultBranch,
            'global_rules' => $data->globalRules,
            'is_active' => $data->isActive,
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $candidate = $base !== '' ? $base : Str::random(8);

        $suffix = 1;
        while (Project::query()->where('slug', $candidate)->exists()) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }
}

