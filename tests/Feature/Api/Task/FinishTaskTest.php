<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\User;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinishTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_can_finish_task_when_claimed(): void
    {
        Carbon::setTestNow('2026-04-16 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Claimed,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-1',
            'locked_until' => Carbon::now()->addMinutes(10),
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-1',
            'status' => TaskExecutionStatus::Claimed,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/finish", [
                'worker_id' => 'worker-1',
                'status' => 'done',
                'execution_summary' => 'ok',
                'failure_reason' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'review')
            ->assertJsonPath('data.review_status', TaskReviewStatus::PendingReview->value);

        $task->refresh();

        $this->assertEquals(TaskStatus::Review, $task->status);
        $this->assertEquals(TaskReviewStatus::PendingReview, $task->review_status);
        $this->assertEquals('ok', $task->execution_summary);
        $this->assertNull($task->failure_reason);
        $this->assertNull($task->locked_until);
        $this->assertEquals(Carbon::now()->toDateTimeString(), $task->finished_at?->toDateTimeString());

        $this->assertDatabaseHas('task_executions', [
            'task_id' => $task->id,
            'worker_id' => 'worker-1',
            'status' => TaskExecutionStatus::Review->value,
            'summary' => 'ok',
        ]);
    }

    public function test_worker_cannot_finish_task_claimed_by_another_worker(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Claimed,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-1',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-1',
            'status' => TaskExecutionStatus::Claimed,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/finish", [
                'worker_id' => 'worker-2',
                'status' => 'done',
            ])
            ->assertForbidden();
    }

    public function test_finish_rejected_for_incompatible_status(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Done,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-1',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-1',
            'status' => TaskExecutionStatus::Done,
            'finished_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/finish", [
                'worker_id' => 'worker-1',
                'status' => 'done',
            ])
            ->assertStatus(409);
    }

    public function test_finish_validates_payload(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Claimed,
            'priority' => TaskPriority::High,
            'claimed_by_worker' => 'worker-1',
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'worker-1',
            'status' => TaskExecutionStatus::Claimed,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/tasks/{$task->id}/finish", [
                'worker_id' => '',
                'status' => 'pending',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['worker_id', 'status']);
    }
}
