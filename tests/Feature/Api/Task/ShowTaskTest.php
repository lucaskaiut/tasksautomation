<?php

namespace Tests\Feature\Api\Task;

use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskAnalysisDomain;
use App\Support\Enums\TaskStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_show_task(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $task = Task::factory()->create([
            'current_stage' => TaskStage::ImplementationFrontend,
            'analysis_domain' => TaskAnalysisDomain::Frontend,
            'analysis_next_stage' => TaskStage::ImplementationFrontend,
            'handoff_to_stage' => TaskStage::ImplementationFrontend,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tasks/'.$task->id)
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.current_stage', 'implementation:frontend')
            ->assertJsonPath('data.analysis.domain', 'frontend')
            ->assertJsonPath('data.handoff.to_stage', 'implementation:frontend');
    }
}
