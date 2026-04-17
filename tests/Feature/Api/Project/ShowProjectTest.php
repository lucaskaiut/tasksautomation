<?php

namespace Tests\Feature\Api\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_show_project(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $project = Project::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/projects/'.$project->id)
            ->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }
}

