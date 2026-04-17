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
use Tests\TestCase;

class TaskExecutionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_executions_requires_authentication(): void
    {
        $task = Task::factory()->create();

        $this->getJson("/api/tasks/{$task->id}/executions")
            ->assertUnauthorized();
    }

    public function test_can_list_executions_for_task(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Medium,
        ]);

        $older = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'w-1',
            'status' => TaskExecutionStatus::Done,
            'finished_at' => now(),
        ]);

        $newer = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'w-2',
            'status' => TaskExecutionStatus::Claimed,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/tasks/{$task->id}/executions");

        $response->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    public function test_can_show_task_execution(): void
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
            'worker_id' => 'w-1',
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
            'summary' => 'Resumo técnico',
            'pull_request_url' => 'https://example.com/pr/1',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/task-executions/{$execution->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $execution->id)
            ->assertJsonPath('data.pull_request_url', 'https://example.com/pr/1');
    }
}
