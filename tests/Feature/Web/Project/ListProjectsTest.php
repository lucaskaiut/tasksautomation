<?php

namespace Tests\Feature\Web\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_projects_index(): void
    {
        $this->get(route('projects.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_projects_index(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Painel administrativo')
            ->assertSee('Projetos')
            ->assertSee('Tarefas')
            ->assertSee('Minha conta')
            ->assertSee($project->name);
    }
}
