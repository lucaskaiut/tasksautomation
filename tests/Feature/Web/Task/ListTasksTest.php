<?php

namespace Tests\Feature\Web\Task;

use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
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
            ->assertSee('bg-red-100', false)
            ->assertSee('Precisa de ajustes')
            ->assertSee('bg-orange-100', false)
            ->assertDontSee('blocked')
            ->assertDontSee('needs_adjustment');
    }
}
