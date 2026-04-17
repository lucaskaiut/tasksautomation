<?php

namespace Tests\Feature\Api\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_show_task(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $task = Task::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tasks/'.$task->id)
            ->assertOk()
            ->assertJsonPath('data.id', $task->id);
    }
}

