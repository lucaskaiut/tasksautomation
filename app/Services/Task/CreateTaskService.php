<?php

namespace App\Services\Task;

use App\Models\ProjectEnvironmentProfile;
use App\Models\Task;
use App\Models\User;
use App\Support\DTOs\TaskData;
use Illuminate\Validation\ValidationException;

final class CreateTaskService
{
    public function handle(TaskData $data, User $creator): Task
    {
        $this->assertEnvironmentProfileBelongsToProject($data->projectId, $data->environmentProfileId);

        return Task::create([
            'project_id' => $data->projectId,
            'environment_profile_id' => $data->environmentProfileId,
            'created_by' => $creator->id,
            'title' => $data->title,
            'description' => $data->description,
            'deliverables' => $data->deliverables,
            'constraints' => $data->constraints,
            'status' => $data->status,
            'priority' => $data->priority,
            'implementation_type' => $data->implementationType,
            'current_stage' => $data->currentStage,
            'analysis_domain' => $data->analysisDomain,
            'analysis_confidence' => $data->analysisConfidence,
            'analysis_next_stage' => $data->analysisNextStage,
            'analysis_summary' => $data->analysisSummary,
            'analysis_evidence' => $data->analysisEvidence,
            'analysis_risks' => $data->analysisRisks,
            'analysis_artifacts' => $data->analysisArtifacts,
            'analysis_notes' => $data->analysisNotes,
            'stage_execution_reference' => $data->stageExecutionReference,
            'stage_execution_stage' => $data->stageExecutionStage,
            'stage_execution_status' => $data->stageExecutionStatus,
            'stage_execution_agent' => $data->stageExecutionAgent,
            'stage_execution_summary' => $data->stageExecutionSummary,
            'stage_execution_output' => $data->stageExecutionOutput,
            'stage_execution_raw_output' => $data->stageExecutionRawOutput,
            'stage_execution_exit_code' => $data->stageExecutionExitCode,
            'stage_execution_started_at' => $data->stageExecutionStartedAt,
            'stage_execution_finished_at' => $data->stageExecutionFinishedAt,
            'stage_execution_context' => $data->stageExecutionContext,
            'handoff_from_stage' => $data->handoffFromStage,
            'handoff_to_stage' => $data->handoffToStage,
            'handoff_reason' => $data->handoffReason,
            'handoff_confidence' => $data->handoffConfidence,
            'handoff_summary' => $data->handoffSummary,
            'handoff_payload' => $data->handoffPayload,
        ]);
    }

    private function assertEnvironmentProfileBelongsToProject(int $projectId, ?int $environmentProfileId): void
    {
        if ($environmentProfileId === null) {
            return;
        }

        $profile = ProjectEnvironmentProfile::query()->find($environmentProfileId);
        if ($profile === null || $profile->project_id !== $projectId) {
            throw ValidationException::withMessages([
                'environment_profile_id' => ['O perfil de ambiente deve pertencer ao mesmo projeto da tarefa.'],
            ]);
        }
    }
}
