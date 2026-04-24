<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStage;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_task(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'current_stage' => TaskStage::Analysis,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/tasks/'.$task->id, [
                'project_id' => $project->id,
                'environment_profile_id' => null,
                'title' => 'Updated title',
                'description' => 'Updated description',
                'deliverables' => null,
                'constraints' => null,
                'status' => 'pending',
                'priority' => 'high',
                'implementation_type' => 'fix',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.implementation_type', 'fix')
            ->assertJsonPath('data.current_stage', TaskStage::Analysis->value);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'priority' => 'high',
            'implementation_type' => 'fix',
            'current_stage' => TaskStage::Analysis->value,
        ]);
    }

    public function test_patch_can_update_only_status(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Pending,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/tasks/'.$task->id, [
                'status' => TaskStatus::Running->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::Running->value);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Running->value,
        ]);
    }

    public function test_patch_validates_fields_that_are_sent(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $task = Task::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/tasks/'.$task->id, [
                'priority' => 'not-a-valid-priority',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_update_validation_errors_return_422(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $task = Task::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/tasks/'.$task->id, [
                'project_id' => $task->project_id,
                'title' => '',
                'description' => '',
                'priority' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description', 'priority', 'implementation_type']);
    }
}
