<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'environment_profile_id' => $this->environment_profile_id,
            'created_by' => $this->created_by,
            'claimed_by_worker' => $this->claimed_by_worker,
            'claimed_at' => $this->claimed_at,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'last_heartbeat_at' => $this->last_heartbeat_at,
            'attempts' => $this->attempts,
            'max_attempts' => $this->max_attempts,
            'locked_until' => $this->locked_until,
            'failure_reason' => $this->failure_reason,
            'execution_summary' => $this->execution_summary,
            'run_after' => $this->run_after,
            'title' => $this->title,
            'description' => $this->description,
            'deliverables' => $this->deliverables,
            'constraints' => $this->constraints,
            'status' => $this->status?->value ?? (string) $this->status,
            'review_status' => $this->review_status?->value ?? (string) $this->review_status,
            'revision_count' => $this->revision_count,
            'last_reviewed_at' => $this->last_reviewed_at,
            'last_reviewed_by' => $this->last_reviewed_by,
            'last_reviewer' => $this->whenLoaded('lastReviewer', function () {
                return [
                    'id' => $this->lastReviewer->id,
                    'name' => $this->lastReviewer->name,
                ];
            }),
            'priority' => $this->priority?->value ?? (string) $this->priority,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'environment_profile' => $this->whenLoaded('environmentProfile', function () {
                return [
                    'id' => $this->environmentProfile->id,
                    'project_id' => $this->environmentProfile->project_id,
                    'name' => $this->environmentProfile->name,
                    'slug' => $this->environmentProfile->slug,
                    'is_default' => $this->environmentProfile->is_default,
                    'docker_compose_yml' => $this->environmentProfile->docker_compose_yml,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
