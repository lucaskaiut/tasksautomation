<?php

namespace Tests\Feature\Api\Task;

use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeTaskStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_history_and_updates_current_stage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $task = Task::factory()->create([
            'current_stage' => TaskStage::Analysis,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/'.$task->id.'/change-stage', [
                'stage' => TaskStage::ImplementationBackend->value,
                'summary' => 'Pronto para implementar no serviço X.',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_stage', TaskStage::ImplementationBackend->value)
            ->assertJsonCount(2, 'data.stage_history');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'current_stage' => TaskStage::ImplementationBackend->value,
        ]);

        $this->assertDatabaseHas('task_stage_histories', [
            'task_id' => $task->id,
            'stage' => TaskStage::ImplementationBackend->value,
        ]);
    }

    public function test_allows_same_stage_with_new_summary(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $task = Task::factory()->create([
            'current_stage' => TaskStage::Analysis,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/'.$task->id.'/change-stage', [
                'stage' => TaskStage::Analysis->value,
                'summary' => 'Nota adicional no mesmo estágio.',
            ])
            ->assertOk()
            ->assertJsonPath('data.current_stage', TaskStage::Analysis->value)
            ->assertJsonCount(2, 'data.stage_history');
    }

    public function test_validation_errors_return_422(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $task = Task::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/'.$task->id.'/change-stage', [
                'stage' => 'invalid-stage',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['stage', 'summary']);
    }

    public function test_rejects_unauthenticated(): void
    {
        $task = Task::factory()->create();

        $this->postJson('/api/tasks/'.$task->id.'/change-stage', [
            'stage' => TaskStage::ImplementationBackend->value,
            'summary' => 'x',
        ])->assertUnauthorized();
    }
}
