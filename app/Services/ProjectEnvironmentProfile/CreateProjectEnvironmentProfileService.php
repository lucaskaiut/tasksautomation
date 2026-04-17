<?php

namespace App\Services\ProjectEnvironmentProfile;

use App\Models\ProjectEnvironmentProfile;
use App\Support\DTOs\ProjectEnvironmentProfileData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateProjectEnvironmentProfileService
{
    public function handle(ProjectEnvironmentProfileData $data): ProjectEnvironmentProfile
    {
        return DB::transaction(function () use ($data) {
            $slug = $data->slug !== null && $data->slug !== '' ? $data->slug : $this->generateUniqueSlug($data->projectId, $data->name);

            if ($data->isDefault) {
                ProjectEnvironmentProfile::query()
                    ->where('project_id', $data->projectId)
                    ->update(['is_default' => false]);
            }

            return ProjectEnvironmentProfile::create([
                'project_id' => $data->projectId,
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'validation_profile' => $data->validationProfile,
                'environment_definition' => $data->environmentDefinition,
                'docker_compose_yml' => $data->dockerComposeYml,
                'is_default' => $data->isDefault,
            ]);
        });
    }

    private function generateUniqueSlug(int $projectId, string $name): string
    {
        $base = Str::slug($name);
        $candidate = $base !== '' ? $base : Str::random(8);

        $suffix = 1;
        while (
            ProjectEnvironmentProfile::query()
                ->where('project_id', $projectId)
                ->where('slug', $candidate)
                ->exists()
        ) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }
}

