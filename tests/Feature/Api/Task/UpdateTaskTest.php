<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
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
            ->assertJsonPath('data.implementation_type', 'fix');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'priority' => 'high',
            'implementation_type' => 'fix',
        ]);
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
