<?php

namespace App\Models;

use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id',
    'environment_profile_id',
    'created_by',
    'title',
    'description',
    'deliverables',
    'constraints',
    'status',
    'priority',
    'claimed_by_worker',
    'claimed_at',
    'started_at',
    'finished_at',
    'last_heartbeat_at',
    'attempts',
    'max_attempts',
    'locked_until',
    'failure_reason',
    'execution_summary',
    'run_after',
    'review_status',
    'revision_count',
    'last_reviewed_at',
    'last_reviewed_by',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function environmentProfile(): BelongsTo
    {
        return $this->belongsTo(ProjectEnvironmentProfile::class, 'environment_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reviewed_by');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(TaskExecution::class)->orderByDesc('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TaskReview::class)->orderByDesc('id');
    }

    public function scopeEligibleForClaim(Builder $query): Builder
    {
        $now = now();

        return $query
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::NeedsAdjustment])
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('run_after')
                    ->orWhere('run_after', '<=', $now);
            })
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('locked_until')
                    ->orWhere('locked_until', '<=', $now);
            })
            ->whereHas('project', function (Builder $query): void {
                $query->where('is_active', true);
            });
    }

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'claimed_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'locked_until' => 'datetime',
            'run_after' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'review_status' => TaskReviewStatus::class,
            'revision_count' => 'integer',
            'last_reviewed_at' => 'datetime',
        ];
    }
}
