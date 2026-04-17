<?php

namespace Tests\Feature\Web\Project;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_project_create(): void
    {
        $this->get(route('projects.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_project_with_valid_data(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Meu Projeto',
            'slug' => 'meu-projeto',
            'description' => 'Descrição',
            'repository_url' => 'https://example.com/repo.git',
            'default_branch' => 'main',
            'global_rules' => '{"notes":"ok"}',
            'is_active' => true,
        ];

        $this->actingAs($user)
            ->post(route('projects.store'), $payload)
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'name' => 'Meu Projeto',
            'slug' => 'meu-projeto',
            'repository_url' => 'https://example.com/repo.git',
        ]);
    }

    public function test_authenticated_user_can_create_project_with_ssh_repository_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'name' => 'Projeto SSH',
                'slug' => 'projeto-ssh',
                'repository_url' => 'git@github.com:acme/repo.git',
            ])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'slug' => 'projeto-ssh',
            'repository_url' => 'git@github.com:acme/repo.git',
        ]);
    }

    public function test_authenticated_user_cannot_create_project_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('projects.create'))
            ->post(route('projects.store'), [
                'name' => '',
            ])
            ->assertRedirect(route('projects.create'))
            ->assertSessionHasErrors(['name', 'repository_url']);

        $this->assertDatabaseCount('projects', 0);
    }
}
