<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Support\DTOs\ProjectData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $value = $this->input('global_rules');

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $this->merge(['global_rules' => $decoded]);
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
                Rule::unique('projects', 'slug')->ignore($project->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'repository_url' => ['required', 'string', 'max:2048', 'url'],
            'default_branch' => ['nullable', 'string', 'max:255'],
            'global_rules' => ['nullable'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function projectData(): ProjectData
    {
        return ProjectData::fromValidated($this->validated());
    }
}
