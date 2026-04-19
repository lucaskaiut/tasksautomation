<?php

namespace Tests\Feature\Api\Task;

use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_without_token_are_blocked(): void
    {
        $this->getJson('/api/tasks')
            ->assertUnauthorized();
    }

    public function test_requests_with_token_can_list_tasks(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        Task::factory()->count(2)->create([
            'current_stage' => TaskStage::ImplementationBackend,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.implementation_type', fn ($value) => in_array($value, ['feature', 'fix'], true))
            ->assertJsonPath('data.0.current_stage', 'implementation:backend')
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
                'message',
            ]);
    }
}
