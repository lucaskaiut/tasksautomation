<?php

namespace Tests\Feature\Api\Task;

use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_task(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create();
        $profile = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks', [
                'project_id' => $project->id,
                'environment_profile_id' => $profile->id,
                'title' => 'API Task',
                'description' => 'Desc',
                'deliverables' => null,
                'constraints' => null,
                'status' => 'pending',
                'priority' => 'low',
                'implementation_type' => 'feature',
                'current_stage' => 'analysis',
                'analysis_domain' => 'backend',
                'analysis_confidence' => 0.92,
                'analysis_next_stage' => 'implementation:backend',
                'analysis_summary' => 'API ready for backend.',
                'analysis_evidence' => ['entrypoint' => 'TaskController'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'API Task')
            ->assertJsonPath('data.implementation_type', 'feature')
            ->assertJsonPath('data.current_stage', 'analysis')
            ->assertJsonPath('data.analysis.domain', 'backend')
            ->assertJsonPath('data.analysis.next_stage', 'implementation:backend')
            ->assertJsonPath('data.analysis.evidence.entrypoint', 'TaskController');

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'environment_profile_id' => $profile->id,
            'created_by' => $user->id,
            'title' => 'API Task',
            'implementation_type' => 'feature',
            'current_stage' => 'analysis',
            'analysis_domain' => 'backend',
        ]);
    }

    public function test_validation_errors_return_422(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks', [
                'project_id' => null,
                'title' => '',
                'description' => '',
                'priority' => 'invalid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id', 'title', 'description', 'priority', 'implementation_type', 'current_stage']);
    }

    public function test_environment_profile_must_belong_to_same_project(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $profileFromOtherProject = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $projectB->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks', [
                'project_id' => $projectA->id,
                'environment_profile_id' => $profileFromOtherProject->id,
                'title' => 'Task',
                'description' => 'Desc',
                'priority' => 'medium',
                'implementation_type' => 'fix',
                'current_stage' => 'analysis',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['environment_profile_id']);
    }
}
