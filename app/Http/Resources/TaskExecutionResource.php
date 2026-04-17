<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TaskExecution
 */
class TaskExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'worker_id' => $this->worker_id,
            'status' => $this->status?->value ?? (string) $this->status,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'summary' => $this->summary,
            'failure_reason' => $this->failure_reason,
            'logs_path' => $this->logs_path,
            'branch_name' => $this->branch_name,
            'commit_sha' => $this->commit_sha,
            'pull_request_url' => $this->pull_request_url,
            'metadata' => $this->metadata,
            'review' => $this->when(
                $this->relationLoaded('review') && $this->review !== null,
                fn () => new TaskReviewResource($this->review)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
