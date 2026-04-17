<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClaimTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_claim_eligible_task(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
            'attempts' => 0,
            'max_attempts' => 3,
            'run_after' => null,
            'locked_until' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.claimed_by_worker', 'worker-local-01');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Claimed->value,
            'claimed_by_worker' => 'worker-local-01',
            'attempts' => 1,
        ]);

        $this->assertDatabaseHas('task_executions', [
            'task_id' => $task->id,
            'worker_id' => 'worker-local-01',
            'status' => TaskExecutionStatus::Claimed->value,
        ]);
    }

    public function test_can_claim_task_in_needs_adjustment_status(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::NeedsAdjustment,
            'priority' => TaskPriority::Medium,
            'attempts' => 0,
            'max_attempts' => 3,
            'run_after' => null,
            'locked_until' => null,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_returns_204_when_no_eligible_task(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertNoContent();
    }

    public function test_does_not_claim_task_with_future_run_after(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
            'run_after' => Carbon::now()->addHour(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertNoContent();
    }

    public function test_does_not_claim_task_when_project_is_inactive(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => false]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertNoContent();
    }

    public function test_does_not_claim_task_with_max_attempts_reached(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
            'attempts' => 3,
            'max_attempts' => 3,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertNoContent();
    }

    public function test_does_not_claim_task_with_valid_lock(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
            'locked_until' => Carbon::now()->addMinute(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertNoContent();
    }

    public function test_selects_task_by_priority_and_created_at(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $lowPriority = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Low,
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        $highPriorityOlder = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::High,
            'created_at' => Carbon::now()->subMinutes(20),
        ]);

        $highPriorityNewer = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::High,
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $highPriorityOlder->id);

        $this->assertDatabaseHas('tasks', [
            'id' => $highPriorityOlder->id,
            'claimed_by_worker' => 'worker-local-01',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $lowPriority->id,
            'claimed_by_worker' => null,
        ]);
    }

    public function test_claim_updates_lock_and_timestamps(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::High,
            'attempts' => 0,
            'locked_until' => null,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/claim', [
                'worker_id' => 'worker-local-01',
            ])
            ->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'claimed_by_worker' => 'worker-local-01',
            'attempts' => 1,
            'status' => TaskStatus::Claimed->value,
        ]);

        $task->refresh();

        $this->assertEquals(Carbon::now()->toDateTimeString(), $task->claimed_at?->toDateTimeString());
        $this->assertEquals(
            Carbon::now()->addMinutes(10)->toDateTimeString(),
            $task->locked_until?->toDateTimeString()
        );
    }
}

