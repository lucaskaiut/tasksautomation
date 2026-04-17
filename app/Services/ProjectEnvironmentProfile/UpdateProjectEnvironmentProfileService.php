<?php

namespace App\Services\ProjectEnvironmentProfile;

use App\Models\ProjectEnvironmentProfile;
use App\Support\DTOs\ProjectEnvironmentProfileData;
use Illuminate\Support\Facades\DB;

final class UpdateProjectEnvironmentProfileService
{
    public function handle(ProjectEnvironmentProfile $profile, ProjectEnvironmentProfileData $data): ProjectEnvironmentProfile
    {
        return DB::transaction(function () use ($profile, $data) {
            if ($data->isDefault) {
                ProjectEnvironmentProfile::query()
                    ->where('project_id', $data->projectId)
                    ->whereKeyNot($profile->id)
                    ->update(['is_default' => false]);
            }

            $payload = [
                'name' => $data->name,
                'description' => $data->description,
                'validation_profile' => $data->validationProfile,
                'environment_definition' => $data->environmentDefinition,
                'docker_compose_yml' => $data->dockerComposeYml,
                'is_default' => $data->isDefault,
            ];

            if ($data->slug !== null && $data->slug !== '') {
                $payload['slug'] = $data->slug;
            }

            $profile->fill($payload);
            $profile->save();

            return $profile->refresh();
        });
    }
}

