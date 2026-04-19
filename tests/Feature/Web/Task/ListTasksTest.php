<?php

namespace Tests\Feature\Web\Task;

use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tasks_index(): void
    {
        $this->get(route('tasks.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_tasks_index(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'implementation_type' => 'fix',
            'status' => TaskStatus::Blocked,
            'review_status' => TaskReviewStatus::NeedsAdjustment,
        ]);

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee('fix')
            ->assertSee('Bloqueada')
            ->assertSee('task-stream-config', false)
            ->assertSee('bg-red-100', false)
            ->assertSee('Precisa de ajustes')
            ->assertSee('bg-orange-100', false);
    }

    public function test_authenticated_user_can_navigate_paginated_task_list(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 21) as $taskNumber) {
            Task::factory()->create([
                'title' => sprintf('Task %02d', $taskNumber),
                'created_at' => Carbon::create(2026, 1, 1, 12, 0, 0)->addMinutes($taskNumber),
            ]);
        }

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Task 21')
            ->assertDontSee('Task 01')
            ->assertSee('?page=2', false)
            ->assertSee('Exibindo 1 a 20 de 21 tarefas.');

        $this->actingAs($user)
            ->get(route('tasks.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Task 01')
            ->assertDontSee('Task 21')
            ->assertSee('Exibindo 21 a 21 de 21 tarefas.');
    }
}
