<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Support\Enums\TaskReviewDecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTaskReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $task = $this->route('task');
        if ($task instanceof Task) {
            return $user->can('view', $task);
        }

        $execution = $this->route('taskExecution');
        if ($execution instanceof TaskExecution) {
            return $user->can('view', $execution->task);
        }

        return false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                'string',
                Rule::in(array_column(TaskReviewDecision::cases(), 'value')),
            ],
            'notes' => [
                Rule::requiredIf(fn (): bool => $this->string('decision')->toString() === TaskReviewDecision::NeedsAdjustment->value),
                'nullable',
                'string',
                'max:20000',
            ],
            'current_behavior' => ['nullable', 'string', 'max:20000'],
            'expected_behavior' => ['nullable', 'string', 'max:20000'],
            'preserve_scope' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->string('decision')->toString() === TaskReviewDecision::NeedsAdjustment->value
                && trim((string) $this->input('notes')) === '') {
                $validator->errors()->add(
                    'notes',
                    'As notas são obrigatórias ao solicitar ajustes.'
                );
            }

            $task = $this->route('task');
            $execution = $this->route('taskExecution');
            if (! $task instanceof Task || ! $execution instanceof TaskExecution) {
                return;
            }
            if ((int) $execution->task_id !== (int) $task->id) {
                $validator->errors()->add(
                    'task_execution',
                    'A execução informada não pertence a esta tarefa.'
                );
            }
        });
    }
}
