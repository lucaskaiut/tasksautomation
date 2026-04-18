<?php

namespace Tests\Feature\Web\Task;

use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_task_create(): void
    {
        $this->get(route('tasks.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_task_with_valid_data(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $profile = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'project_id' => $project->id,
                'environment_profile_id' => $profile->id,
                'title' => 'Minha tarefa',
                'description' => 'Descrição',
                'deliverables' => 'Entregáveis',
                'constraints' => 'Restrições',
                'status' => 'pending',
                'priority' => 'medium',
                'implementation_type' => 'feature',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'environment_profile_id' => $profile->id,
            'created_by' => $user->id,
            'title' => 'Minha tarefa',
            'implementation_type' => 'feature',
        ]);
    }

    public function test_authenticated_user_sees_status_labels_in_portuguese_on_create_form(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        ProjectEnvironmentProfile::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('Rascunho')
            ->assertSee('Pendente')
            ->assertSee('Em revisão')
            ->assertSee('Precisa de ajustes');
    }

    public function test_create_form_exposes_environment_profiles_for_selected_project_filtering(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create(['name' => 'Projeto Alpha']);
        $projectB = Project::factory()->create(['name' => 'Projeto Beta']);
        $profileA = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $projectA->id,
            'name' => 'Alpha Full',
            'slug' => 'alpha-full',
        ]);
        $profileB = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $projectB->id,
            'name' => 'Beta Light',
            'slug' => 'beta-light',
        ]);

        $this->actingAs($user)
            ->from(route('tasks.create'))
            ->post(route('tasks.store'), [
                'project_id' => $projectA->id,
                'environment_profile_id' => $profileA->id,
                'title' => '',
                'description' => '',
                'priority' => 'invalid',
            ])
            ->assertRedirect(route('tasks.create'))
            ->assertSessionHasErrors(['title', 'description', 'priority', 'implementation_type']);

        $this->actingAs($user)
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('x-model="selectedProjectId"', false)
            ->assertSee('x-model="selectedEnvironmentProfileId"', false)
            ->assertSee('"project_id":'.$projectA->id, false)
            ->assertSee('"project_id":'.$projectB->id, false)
            ->assertSee('"name":"Alpha Full"', false)
            ->assertSee('"name":"Beta Light"', false)
            ->assertSee('"slug":"alpha-full"', false)
            ->assertSee('"slug":"beta-light"', false);
    }

    public function test_authenticated_user_cannot_create_task_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->from(route('tasks.create'))
            ->post(route('tasks.store'), [
                'project_id' => $project->id,
                'title' => '',
                'description' => '',
                'priority' => 'invalid',
            ])
            ->assertRedirect(route('tasks.create'))
            ->assertSessionHasErrors(['title', 'description', 'priority', 'implementation_type']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_environment_profile_must_belong_to_same_project(): void
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $profileFromOtherProject = ProjectEnvironmentProfile::factory()->create([
            'project_id' => $projectB->id,
        ]);

        $this->actingAs($user)
            ->from(route('tasks.create'))
            ->post(route('tasks.store'), [
                'project_id' => $projectA->id,
                'environment_profile_id' => $profileFromOtherProject->id,
                'title' => 'Tarefa',
                'description' => 'Descrição',
                'priority' => 'low',
                'implementation_type' => 'fix',
            ])
            ->assertRedirect(route('tasks.create'))
            ->assertSessionHasErrors(['environment_profile_id']);

        $this->assertDatabaseCount('tasks', 0);
    }
}
