<?php

namespace App\Models;

use App\Support\Enums\TaskStage;
use Database\Factories\TaskStageHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'stage',
    'summary',
])]
class TaskStageHistory extends Model
{
    /** @use HasFactory<TaskStageHistoryFactory> */
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    protected function casts(): array
    {
        return [
            'stage' => TaskStage::class,
        ];
    }
}
