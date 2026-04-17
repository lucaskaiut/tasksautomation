<?php

namespace Tests\Feature\Web\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_project_edit(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.edit', $project))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_project_with_valid_data(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'slug' => 'projeto-1',
        ]);

        $this->actingAs($user)
            ->put(route('projects.update', $project), [
                'name' => 'Projeto Atualizado',
                'slug' => 'projeto-1',
                'description' => 'Nova descrição',
                'repository_url' => 'https://example.com/new.git',
                'default_branch' => 'main',
                'global_rules' => '{"a":1}',
                'is_active' => true,
            ])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Projeto Atualizado',
            'repository_url' => 'https://example.com/new.git',
        ]);
    }

    public function test_authenticated_user_cannot_update_project_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->from(route('projects.edit', $project))
            ->put(route('projects.update', $project), [
                'name' => '',
                'slug' => $project->slug,
                'repository_url' => 'bad',
                'default_branch' => 'main',
            ])
            ->assertRedirect(route('projects.edit', $project))
            ->assertSessionHasErrors(['name', 'repository_url']);
    }
}

