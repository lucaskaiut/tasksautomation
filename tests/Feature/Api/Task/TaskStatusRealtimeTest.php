<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\User;
use App\Services\Realtime\TaskStreamPublisher;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery\MockInterface;
use Tests\TestCase;

class TaskStatusRealtimeTest extends TestCase
{
    use DatabaseMigrations;

    public function test_creating_task_publishes_created_event(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->mock(TaskStreamPublisher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('publish')
                ->once()
                ->withArgs(function (array $payload): bool {
                    return $payload['type'] === 'task.created'
                        && ($payload['task']['title'] ?? null) === 'Tarefa criada'
                        && ($payload['task']['status'] ?? null) === TaskStatus::Pending->value;
                });
        });

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'project_id' => $project->id,
                'environment_profile_id' => null,
                'title' => 'Tarefa criada',
                'description' => 'Descrição criada',
                'deliverables' => null,
                'constraints' => null,
                'status' => TaskStatus::Pending->value,
                'priority' => TaskPriority::Medium->value,
                'implementation_type' => 'feature',
                'current_stage' => 'analysis',
            ])
            ->assertRedirect(route('tasks.index'));
    }

    public function test_claim_publishes_updated_event_without_service_level_hook(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
        ]);

        $this->mock(TaskStreamPublisher::class, function (MockInterface $mock) use ($task): void {
            $mock->shouldReceive('publish')
                ->once()
                ->withArgs(function (array $payload) use ($task): bool {
                    return $payload['type'] === 'task.updated'
                        && (int) $payload['task_id'] === $task->id
                        && ($payload['changes']['status']['from'] ?? null) === TaskStatus::Pending->value
                        && ($payload['changes']['status']['to'] ?? null) === TaskStatus::Claimed->value;
                });
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertOk();
    }

    public function test_heartbeat_publishes_generic_updated_event_for_runtime_fields(): void
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

        $this->mock(TaskStreamPublisher::class, function (MockInterface $mock) use ($task): void {
            $mock->shouldReceive('publish')
                ->once()
                ->withArgs(function (array $payload) use ($task): bool {
                    return $payload['type'] === 'task.updated'
                        && (int) $payload['task_id'] === $task->id
                        && array_key_exists('last_heartbeat_at', $payload['changes'])
                        && array_key_exists('locked_until', $payload['changes']);
                });
        });

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/heartbeat", [
                'worker_id' => 'worker-local-01',
            ])
            ->assertOk();
    }

    public function test_deleting_task_publishes_deleted_event(): void
    {
        $task = Task::factory()->create();

        $this->mock(TaskStreamPublisher::class, function (MockInterface $mock) use ($task): void {
            $mock->shouldReceive('publish')
                ->once()
                ->withArgs(function (array $payload) use ($task): bool {
                    return $payload['type'] === 'task.deleted'
                        && (int) $payload['task_id'] === $task->id
                        && ($payload['task']['title'] ?? null) === $task->title;
                });
        });

        $task->delete();
    }
}
