<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\User;
use App\Services\Realtime\TaskStatusStreamPublisher;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskReviewDecision;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TaskStatusRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_publishes_status_change_event(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
        ]);

        $this->mock(TaskStatusStreamPublisher::class, function (MockInterface $mock) use ($task): void {
            $mock->shouldReceive('publishStatusChange')
                ->once()
                ->withArgs(function (Task $publishedTask, ?string $previousStatus) use ($task): bool {
                    return $publishedTask->is($task) && $previousStatus === TaskStatus::Pending->value;
                });
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertOk();
    }

    public function test_running_heartbeat_does_not_publish_when_status_is_unchanged(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Running,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-local-01',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-local-01',
            'status' => TaskExecutionStatus::Running,
            'started_at' => now(),
        ]);

        $this->mock(TaskStatusStreamPublisher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('publishStatusChange')->never();
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/heartbeat", [
                'worker_id' => 'worker-local-01',
            ])
            ->assertOk();
    }

    public function test_update_publishes_only_when_status_changes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create();

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Draft,
        ]);

        $this->mock(TaskStatusStreamPublisher::class, function (MockInterface $mock) use ($task): void {
            $mock->shouldReceive('publishStatusChange')
                ->once()
                ->withArgs(function (Task $publishedTask, ?string $previousStatus) use ($task): bool {
                    return $publishedTask->is($task) && $previousStatus === TaskStatus::Draft->value;
                });
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/tasks/{$task->id}", [
                'project_id' => $project->id,
                'environment_profile_id' => null,
                'title' => 'Atualizada',
                'description' => 'Nova descrição',
                'deliverables' => null,
                'constraints' => null,
                'status' => TaskStatus::Pending->value,
                'priority' => 'high',
                'implementation_type' => 'fix',
            ])
            ->assertOk();
    }

    public function test_review_publishes_status_change_event(): void
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

        $this->mock(TaskStatusStreamPublisher::class, function (MockInterface $mock) use ($task): void {
            $mock->shouldReceive('publishStatusChange')
                ->once()
                ->withArgs(function (Task $publishedTask, ?string $previousStatus) use ($task): bool {
                    return $publishedTask->is($task) && $previousStatus === TaskStatus::Review->value;
                });
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/task-executions/{$execution->id}/reviews", [
                'decision' => TaskReviewDecision::Approved->value,
                'notes' => 'Aprovado.',
            ])
            ->assertCreated();
    }
}
