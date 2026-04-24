<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Support\DTOs\TaskData;
use App\Support\Enums\TaskImplementationType;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStage;
use App\Support\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Task::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'environment_profile_id' => ['nullable', 'integer', 'exists:project_environment_profiles,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'deliverables' => ['nullable', 'string'],
            'constraints' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'priority' => ['required', 'string', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'implementation_type' => ['required', 'string', Rule::in(array_column(TaskImplementationType::cases(), 'value'))],
            'current_stage' => ['required', 'string', Rule::in(array_column(TaskStage::cases(), 'value'))],
        ];
    }

    public function taskData(): TaskData
    {
        return TaskData::fromValidated($this->validated());
    }
}
