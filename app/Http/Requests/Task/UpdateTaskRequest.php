<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Support\DTOs\TaskData;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && (bool) $this->user()?->can('update', $task);
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
        ];
    }

    public function taskData(): TaskData
    {
        return TaskData::fromValidated($this->validated());
    }
}
