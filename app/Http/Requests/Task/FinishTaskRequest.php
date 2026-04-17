<?php

namespace App\Http\Requests\Task;

use App\Support\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinishTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'string', 'max:255'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    TaskStatus::Done->value,
                    TaskStatus::Failed->value,
                    TaskStatus::Review->value,
                    TaskStatus::Blocked->value,
                    TaskStatus::Cancelled->value,
                ]),
            ],
            'execution_summary' => ['nullable', 'string'],
            'failure_reason' => ['nullable', 'string'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'commit_sha' => ['nullable', 'string', 'max:255'],
            'pull_request_url' => ['nullable', 'string', 'max:2048'],
            'logs_path' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

