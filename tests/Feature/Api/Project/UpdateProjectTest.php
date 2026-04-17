<?php

namespace Tests\Feature\Api\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_project(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create([
            'slug' => 'proj',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/projects/'.$project->id, [
                'name' => 'Updated',
                'slug' => 'proj',
                'description' => null,
                'repository_url' => 'https://example.com/x.git',
                'default_branch' => 'main',
                'global_rules' => null,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated',
        ]);
    }

    public function test_can_update_project_with_ssh_repository_address(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create([
            'slug' => 'ssh-proj',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/projects/'.$project->id, [
                'name' => 'Updated SSH',
                'slug' => 'ssh-proj',
                'description' => null,
                'repository_url' => 'git@github.com:acme/updated.git',
                'default_branch' => 'main',
                'global_rules' => null,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.repository_url', 'git@github.com:acme/updated.git');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'repository_url' => 'git@github.com:acme/updated.git',
        ]);
    }

    public function test_update_validation_errors_return_422(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/projects/'.$project->id, [
                'name' => '',
                'slug' => $project->slug,
                'default_branch' => 'main',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'repository_url']);
    }
}
