<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\User;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskReviewDecision;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskReviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_review_requires_authentication(): void
    {
        $execution = TaskExecution::factory()->create();

        $this->postJson("/api/task-executions/{$execution->id}/reviews", [
            'decision' => TaskReviewDecision::Approved->value,
            'notes' => 'ok',
        ])->assertUnauthorized();
    }

    public function test_can_approve_execution_via_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
            'priority' => TaskPriority::Medium,
            'review_status' => TaskReviewStatus::PendingReview,
        ]);

        $execution = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'w-1',
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/task-executions/{$execution->id}/reviews", [
                'decision' => TaskReviewDecision::Approved->value,
                'notes' => 'Aprovado.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.decision', TaskReviewDecision::Approved->value);

        $task->refresh();

        $this->assertEquals(TaskStatus::Done, $task->status);
        $this->assertEquals(TaskReviewStatus::Approved, $task->review_status);
        $this->assertEquals(TaskExecutionStatus::Done, $execution->refresh()->status);
    }

    public function test_can_request_adjustment_via_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
            'priority' => TaskPriority::Medium,
            'review_status' => TaskReviewStatus::PendingReview,
            'revision_count' => 0,
        ]);

        $execution = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'w-1',
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/task-executions/{$execution->id}/reviews", [
                'decision' => TaskReviewDecision::NeedsAdjustment->value,
                'notes' => 'Corrigir fluxo X.',
                'current_behavior' => 'Quebra em Y',
                'expected_behavior' => 'Deve Z',
                'preserve_scope' => 'Manter autenticação',
            ])
            ->assertCreated();

        $task->refresh();

        $this->assertEquals(TaskStatus::Pending, $task->status);
        $this->assertEquals(TaskReviewStatus::NeedsAdjustment, $task->review_status);
        $this->assertSame(1, $task->revision_count);
        $this->assertNull($task->claimed_by_worker);
    }

    public function test_cannot_submit_second_review_for_same_execution(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
            'review_status' => TaskReviewStatus::PendingReview,
        ]);

        $execution = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/task-executions/{$execution->id}/reviews", [
                'decision' => TaskReviewDecision::Approved->value,
                'notes' => 'ok',
            ])
            ->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/task-executions/{$execution->id}/reviews", [
                'decision' => TaskReviewDecision::Approved->value,
                'notes' => 'again',
            ])
            ->assertStatus(409);
    }

    public function test_invalid_payload_returns_validation_errors(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
        ]);

        $execution = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/task-executions/{$execution->id}/reviews", [
                'decision' => TaskReviewDecision::NeedsAdjustment->value,
                'notes' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['notes']);
    }
}
