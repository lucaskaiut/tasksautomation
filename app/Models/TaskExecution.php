<?php

namespace App\Models;

use App\Support\Enums\TaskExecutionStatus;
use Database\Factories\TaskExecutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'task_id',
    'worker_id',
    'status',
    'started_at',
    'finished_at',
    'summary',
    'failure_reason',
    'logs_path',
    'branch_name',
    'commit_sha',
    'pull_request_url',
    'metadata',
])]
class TaskExecution extends Model
{
    /** @use HasFactory<TaskExecutionFactory> */
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(TaskReview::class);
    }

    protected function casts(): array
    {
        return [
            'status' => TaskExecutionStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
