<?php

namespace App\Models;

use App\Support\Enums\TaskReviewDecision;
use Database\Factories\TaskReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'task_execution_id',
    'created_by',
    'decision',
    'notes',
    'current_behavior',
    'expected_behavior',
    'preserve_scope',
])]
class TaskReview extends Model
{
    /** @use HasFactory<TaskReviewFactory> */
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function taskExecution(): BelongsTo
    {
        return $this->belongsTo(TaskExecution::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'decision' => TaskReviewDecision::class,
        ];
    }
}
