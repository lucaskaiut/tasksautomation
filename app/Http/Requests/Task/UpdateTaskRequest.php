<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Support\DTOs\TaskData;
use App\Support\Enums\TaskImplementationType;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && (bool) $this->user()?->can('update', $task);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->putRules();

        if ($this->isMethod('PATCH')) {
            return $this->rulesWithSometimes($rules);
        }

        return $rules;
    }

    public function taskData(): TaskData
    {
        $task = $this->route('task');
        assert($task instanceof Task);

        $validated = $this->validated();
        $validated['current_stage'] = $task->current_stage->value;

        if ($this->isMethod('PATCH')) {
            return TaskData::forPartialUpdate($task, $validated);
        }

        return TaskData::fromValidated($validated);
    }

    /**
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, array<int, mixed>>
     */
    private function rulesWithSometimes(array $rules): array
    {
        $out = [];

        foreach ($rules as $key => $fieldRules) {
            $out[$key] = array_merge(['sometimes'], $fieldRules);
        }

        return $out;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function putRules(): array
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
        ];
    }
}
