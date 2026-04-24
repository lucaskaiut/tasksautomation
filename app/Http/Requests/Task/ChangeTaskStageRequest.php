<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Support\Enums\TaskStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTaskStageRequest extends FormRequest
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
        return [
            'stage' => ['required', 'string', Rule::in(array_column(TaskStage::cases(), 'value'))],
            'summary' => ['required', 'string', 'max:65535'],
        ];
    }

    public function toStage(): TaskStage
    {
        return TaskStage::from($this->string('stage')->toString());
    }

    public function summaryText(): string
    {
        return $this->string('summary')->toString();
    }
}
