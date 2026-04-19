<?php

namespace App\Support\DTOs;

use App\Support\Enums\TaskAnalysisDomain;
use App\Support\Enums\TaskImplementationType;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStage;
use App\Support\Enums\TaskStatus;

final readonly class TaskData
{
    /**
     * @param  array<mixed>|null  $analysisEvidence
     * @param  array<mixed>|null  $analysisRisks
     * @param  array<mixed>|null  $analysisArtifacts
     * @param  array<mixed>|null  $stageExecutionOutput
     * @param  array<mixed>|null  $stageExecutionContext
     * @param  array<mixed>|null  $handoffPayload
     */
    public function __construct(
        public int $projectId,
        public ?int $environmentProfileId,
        public string $title,
        public string $description,
        public ?string $deliverables,
        public ?string $constraints,
        public TaskStatus $status,
        public TaskPriority $priority,
        public TaskImplementationType $implementationType,
        public TaskStage $currentStage,
        public ?TaskAnalysisDomain $analysisDomain,
        public ?float $analysisConfidence,
        public ?TaskStage $analysisNextStage,
        public ?string $analysisSummary,
        public ?array $analysisEvidence,
        public ?array $analysisRisks,
        public ?array $analysisArtifacts,
        public ?string $analysisNotes,
        public ?string $stageExecutionReference,
        public ?TaskStage $stageExecutionStage,
        public ?string $stageExecutionStatus,
        public ?string $stageExecutionAgent,
        public ?string $stageExecutionSummary,
        public ?array $stageExecutionOutput,
        public ?string $stageExecutionRawOutput,
        public ?int $stageExecutionExitCode,
        public ?string $stageExecutionStartedAt,
        public ?string $stageExecutionFinishedAt,
        public ?array $stageExecutionContext,
        public ?TaskStage $handoffFromStage,
        public ?TaskStage $handoffToStage,
        public ?string $handoffReason,
        public ?float $handoffConfidence,
        public ?string $handoffSummary,
        public ?array $handoffPayload,
    ) {}

    /**
     * @param  array<string,mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            projectId: (int) $validated['project_id'],
            environmentProfileId: array_key_exists('environment_profile_id', $validated) && $validated['environment_profile_id'] !== null
                ? (int) $validated['environment_profile_id']
                : null,
            title: $validated['title'],
            description: $validated['description'],
            deliverables: $validated['deliverables'] ?? null,
            constraints: $validated['constraints'] ?? null,
            status: TaskStatus::from($validated['status'] ?? TaskStatus::Pending->value),
            priority: TaskPriority::from($validated['priority']),
            implementationType: TaskImplementationType::from($validated['implementation_type']),
            currentStage: TaskStage::from($validated['current_stage']),
            analysisDomain: filled($validated['analysis_domain'] ?? null)
                ? TaskAnalysisDomain::from($validated['analysis_domain'])
                : null,
            analysisConfidence: filled($validated['analysis_confidence'] ?? null)
                ? (float) $validated['analysis_confidence']
                : null,
            analysisNextStage: filled($validated['analysis_next_stage'] ?? null)
                ? TaskStage::from($validated['analysis_next_stage'])
                : null,
            analysisSummary: $validated['analysis_summary'] ?? null,
            analysisEvidence: self::decodeNullableJson($validated['analysis_evidence'] ?? null),
            analysisRisks: self::decodeNullableJson($validated['analysis_risks'] ?? null),
            analysisArtifacts: self::decodeNullableJson($validated['analysis_artifacts'] ?? null),
            analysisNotes: $validated['analysis_notes'] ?? null,
            stageExecutionReference: $validated['stage_execution_reference'] ?? null,
            stageExecutionStage: filled($validated['stage_execution_stage'] ?? null)
                ? TaskStage::from($validated['stage_execution_stage'])
                : null,
            stageExecutionStatus: $validated['stage_execution_status'] ?? null,
            stageExecutionAgent: $validated['stage_execution_agent'] ?? null,
            stageExecutionSummary: $validated['stage_execution_summary'] ?? null,
            stageExecutionOutput: self::decodeNullableJson($validated['stage_execution_output'] ?? null),
            stageExecutionRawOutput: $validated['stage_execution_raw_output'] ?? null,
            stageExecutionExitCode: filled($validated['stage_execution_exit_code'] ?? null)
                ? (int) $validated['stage_execution_exit_code']
                : null,
            stageExecutionStartedAt: $validated['stage_execution_started_at'] ?? null,
            stageExecutionFinishedAt: $validated['stage_execution_finished_at'] ?? null,
            stageExecutionContext: self::decodeNullableJson($validated['stage_execution_context'] ?? null),
            handoffFromStage: filled($validated['handoff_from_stage'] ?? null)
                ? TaskStage::from($validated['handoff_from_stage'])
                : null,
            handoffToStage: filled($validated['handoff_to_stage'] ?? null)
                ? TaskStage::from($validated['handoff_to_stage'])
                : null,
            handoffReason: $validated['handoff_reason'] ?? null,
            handoffConfidence: filled($validated['handoff_confidence'] ?? null)
                ? (float) $validated['handoff_confidence']
                : null,
            handoffSummary: $validated['handoff_summary'] ?? null,
            handoffPayload: self::decodeNullableJson($validated['handoff_payload'] ?? null),
        );
    }

    /**
     * @return array<mixed>|null
     */
    private static function decodeNullableJson(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        /** @var array<mixed>|null $decoded */
        $decoded = json_decode((string) $value, true);

        return $decoded;
    }
}
