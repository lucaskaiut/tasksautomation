<?php

namespace App\Http\Requests\ProjectEnvironmentProfile;

use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use App\Support\DTOs\ProjectEnvironmentProfileData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectEnvironmentProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['validation_profile', 'environment_definition'] as $key) {
            $value = $this->input($key);
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $this->merge([$key => $decoded]);
                }
            }
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && (bool) $this->user()?->can('update', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('project_environment_profiles', 'slug')->where('project_id', $project->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'validation_profile' => ['nullable'],
            'environment_definition' => ['nullable'],
            'docker_compose_yml' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function profileData(): ProjectEnvironmentProfileData
    {
        /** @var Project $project */
        $project = $this->route('project');

        return ProjectEnvironmentProfileData::fromValidated($project->id, $this->validated());
    }
}
