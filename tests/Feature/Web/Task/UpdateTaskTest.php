<?php

namespace Tests\Feature\Web\Task;

use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_task_edit(): void
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.edit', $task))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_task_with_valid_data(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $profile = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $project->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'environment_profile_id' => null,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), [
                'project_id' => $project->id,
                'environment_profile_id' => $profile->id,
                'title' => 'Atualizada',
                'description' => 'Nova descrição',
                'deliverables' => null,
                'constraints' => null,
                'status' => 'draft',
                'priority' => 'high',
                'implementation_type' => 'fix',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'environment_profile_id' => $profile->id,
            'title' => 'Atualizada',
            'status' => 'draft',
            'priority' => 'high',
            'implementation_type' => 'fix',
        ]);
    }

    public function test_authenticated_user_cannot_update_task_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $this->actingAs($user)
            ->from(route('tasks.edit', $task))
            ->put(route('tasks.update', $task), [
                'project_id' => $task->project_id,
                'title' => '',
                'description' => '',
                'priority' => 'invalid',
            ])
            ->assertRedirect(route('tasks.edit', $task))
            ->assertSessionHasErrors(['title', 'description', 'priority', 'implementation_type']);
    }

    public function test_environment_profile_must_belong_to_same_project_on_update(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $task = Task::factory()->create([
            'project_id' => $projectA->id,
            'created_by' => $user->id,
        ]);

        $profileFromOtherProject = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $projectB->id,
        ]);

        $this->actingAs($user)
            ->from(route('tasks.edit', $task))
            ->put(route('tasks.update', $task), [
                'project_id' => $projectA->id,
                'environment_profile_id' => $profileFromOtherProject->id,
                'title' => 'Tarefa',
                'description' => 'Descrição',
                'priority' => 'low',
                'implementation_type' => 'feature',
            ])
            ->assertRedirect(route('tasks.edit', $task))
            ->assertSessionHasErrors(['environment_profile_id']);
    }
}
