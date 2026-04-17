<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\User;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_accepted_for_claimed_task(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Claimed,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-local-01',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-local-01',
            'status' => TaskExecutionStatus::Claimed,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/heartbeat", [
                'worker_id' => 'worker-local-01',
            ]);

        $response->assertOk();

        $task->refresh();

        $this->assertEquals(TaskStatus::Running, $task->status);
        $this->assertEquals(Carbon::now()->toDateTimeString(), $task->last_heartbeat_at?->toDateTimeString());
        $this->assertEquals(
            Carbon::now()->addMinutes(10)->toDateTimeString(),
            $task->locked_until?->toDateTimeString()
        );
    }

    public function test_heartbeat_accepted_for_running_task(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

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
            'started_at' => Carbon::now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/heartbeat", [
                'worker_id' => 'worker-local-01',
            ])
            ->assertOk();

        $task->refresh();

        $this->assertEquals(Carbon::now()->toDateTimeString(), $task->last_heartbeat_at?->toDateTimeString());
        $this->assertEquals(
            Carbon::now()->addMinutes(10)->toDateTimeString(),
            $task->locked_until?->toDateTimeString()
        );
    }

    public function test_heartbeat_rejected_for_different_worker(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Claimed,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-local-01',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-local-01',
            'status' => TaskExecutionStatus::Claimed,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/heartbeat", [
                'worker_id' => 'worker-other',
            ])
            ->assertForbidden();
    }

    public function test_heartbeat_rejected_for_incompatible_status(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Done,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-local-01',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-local-01',
            'status' => TaskExecutionStatus::Done,
            'finished_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/heartbeat", [
                'worker_id' => 'worker-local-01',
            ])
            ->assertStatus(409);
    }

    public function test_heartbeat_requires_authentication_when_route_is_protected(): void
    {
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Claimed,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-local-01',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-local-01',
            'status' => TaskExecutionStatus::Claimed,
        ]);

        $this->postJson("/api/tasks/{$task->id}/heartbeat", [
            'worker_id' => 'worker-local-01',
        ])
            ->assertUnauthorized();
    }
}
