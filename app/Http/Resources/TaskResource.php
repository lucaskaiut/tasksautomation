<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
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
            'implementation_type' => $this->implementation_type?->value ?? (string) $this->implementation_type,
            'current_stage' => $this->current_stage?->value ?? (string) $this->current_stage,
            'analysis' => [
                'domain' => $this->analysis_domain?->value ?? $this->analysis_domain,
                'confidence' => $this->analysis_confidence,
                'next_stage' => $this->analysis_next_stage?->value ?? $this->analysis_next_stage,
                'summary' => $this->analysis_summary,
                'evidence' => $this->analysis_evidence,
                'risks' => $this->analysis_risks,
                'artifacts' => $this->analysis_artifacts,
                'notes' => $this->analysis_notes,
            ],
            'stage_execution' => [
                'reference' => $this->stage_execution_reference,
                'stage' => $this->stage_execution_stage?->value ?? $this->stage_execution_stage,
                'status' => $this->stage_execution_status,
                'agent' => $this->stage_execution_agent,
                'summary' => $this->stage_execution_summary,
                'output' => $this->stage_execution_output,
                'raw_output' => $this->stage_execution_raw_output,
                'exit_code' => $this->stage_execution_exit_code,
                'started_at' => $this->stage_execution_started_at,
                'finished_at' => $this->stage_execution_finished_at,
                'context' => $this->stage_execution_context,
            ],
            'handoff' => [
                'from_stage' => $this->handoff_from_stage?->value ?? $this->handoff_from_stage,
                'to_stage' => $this->handoff_to_stage?->value ?? $this->handoff_to_stage,
                'reason' => $this->handoff_reason,
                'confidence' => $this->handoff_confidence,
                'summary' => $this->handoff_summary,
                'payload' => $this->handoff_payload,
            ],
            'review_status' => $this->review_status?->value ?? (string) $this->review_status,
            'revision_count' => $this->revision_count,
            'last_reviewed_at' => $this->last_reviewed_at,
            'last_reviewed_by' => $this->last_reviewed_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
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
