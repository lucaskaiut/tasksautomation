<?php

namespace Tests\Feature\Web\Task;

use App\Models\Task;
use App\Models\User;
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
        ]);

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee('fix');
    }
}
