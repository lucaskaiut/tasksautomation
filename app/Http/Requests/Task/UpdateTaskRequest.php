<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Support\DTOs\TaskData;
use App\Support\Enums\TaskAnalysisDomain;
use App\Support\Enums\TaskImplementationType;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStage;
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

    protected function prepareForValidation(): void
    {
        $this->normalizeJsonFields([
            'analysis_evidence',
            'analysis_risks',
            'analysis_artifacts',
            'stage_execution_output',
            'stage_execution_context',
            'handoff_payload',
        ]);
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
            'implementation_type' => ['required', 'string', Rule::in(array_column(TaskImplementationType::cases(), 'value'))],
            'current_stage' => ['required', 'string', Rule::in(array_column(TaskStage::cases(), 'value'))],
            'analysis_domain' => ['nullable', 'string', Rule::in(array_column(TaskAnalysisDomain::cases(), 'value'))],
            'analysis_confidence' => ['nullable', 'numeric', 'between:0,1'],
            'analysis_next_stage' => ['nullable', 'string', Rule::in(array_column(TaskStage::cases(), 'value'))],
            'analysis_summary' => ['nullable', 'string'],
            'analysis_evidence' => ['nullable', 'json'],
            'analysis_risks' => ['nullable', 'json'],
            'analysis_artifacts' => ['nullable', 'json'],
            'analysis_notes' => ['nullable', 'string'],
            'stage_execution_reference' => ['nullable', 'string', 'max:255'],
            'stage_execution_stage' => ['nullable', 'string', Rule::in(array_column(TaskStage::cases(), 'value'))],
            'stage_execution_status' => ['nullable', 'string', 'max:255'],
            'stage_execution_agent' => ['nullable', 'string', 'max:255'],
            'stage_execution_summary' => ['nullable', 'string'],
            'stage_execution_output' => ['nullable', 'json'],
            'stage_execution_raw_output' => ['nullable', 'string'],
            'stage_execution_exit_code' => ['nullable', 'integer'],
            'stage_execution_started_at' => ['nullable', 'date'],
            'stage_execution_finished_at' => ['nullable', 'date', 'after_or_equal:stage_execution_started_at'],
            'stage_execution_context' => ['nullable', 'json'],
            'handoff_from_stage' => ['nullable', 'string', Rule::in(array_column(TaskStage::cases(), 'value'))],
            'handoff_to_stage' => ['nullable', 'string', Rule::in(array_column(TaskStage::cases(), 'value'))],
            'handoff_reason' => ['nullable', 'string'],
            'handoff_confidence' => ['nullable', 'numeric', 'between:0,1'],
            'handoff_summary' => ['nullable', 'string'],
            'handoff_payload' => ['nullable', 'json'],
        ];
    }

    public function taskData(): TaskData
    {
        return TaskData::fromValidated($this->validated());
    }

    /**
     * @param  list<string>  $fields
     */
    private function normalizeJsonFields(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (is_array($value)) {
                $normalized[$field] = json_encode($value);
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
