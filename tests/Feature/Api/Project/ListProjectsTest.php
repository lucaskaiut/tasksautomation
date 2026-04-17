<?php

namespace Tests\Feature\Api\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_without_token_are_blocked(): void
    {
        $this->getJson('/api/projects')
            ->assertUnauthorized();
    }

    public function test_requests_with_token_can_list_projects(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        Project::factory()->count(2)->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
                'message',
            ]);
    }
}

