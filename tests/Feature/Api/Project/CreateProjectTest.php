<?php

namespace Tests\Feature\Api\Project;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_project(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/projects', [
                'name' => 'API Project',
                'slug' => 'api-project',
                'description' => 'Desc',
                'repository_url' => 'https://example.com/repo.git',
                'default_branch' => 'main',
                'global_rules' => ['a' => 1],
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'api-project');

        $this->assertDatabaseHas('projects', [
            'slug' => 'api-project',
            'name' => 'API Project',
        ]);
    }

    public function test_validation_errors_return_422(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/projects', [
                'name' => '',
                'repository_url' => 'bad',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'repository_url']);
    }
}

