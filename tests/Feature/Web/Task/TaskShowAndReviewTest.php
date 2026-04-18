<?php

namespace Tests\Feature\Web\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\User;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskReviewDecision;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskShowAndReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_task_show(): void
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.show', $task))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_task_detail_with_histories(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
            'priority' => TaskPriority::Medium,
            'review_status' => TaskReviewStatus::PendingReview,
        ]);

        TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'w-1',
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
            'summary' => 'Build ok',
        ]);

        $this->actingAs($user)
            ->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee($task->title)
            ->assertSee('Voltar à lista')
            ->assertSee('task-stream-config', false)
            ->assertSee('Histórico de execuções')
            ->assertSee('Histórico de revisões')
            ->assertSee('Registrar revisão funcional')
            ->assertSee('Em revisão')
            ->assertSee('bg-violet-100', false);
    }

    public function test_user_can_approve_and_task_becomes_done(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
            'review_status' => TaskReviewStatus::PendingReview,
        ]);

        $execution = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'w-1',
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('tasks.executions.reviews.store', [$task, $execution]), [
                'decision' => TaskReviewDecision::Approved->value,
                'notes' => 'LGTM',
            ])
            ->assertRedirect(route('tasks.show', $task));

        $task->refresh();

        $this->assertEquals(TaskStatus::Done, $task->status);
        $this->assertEquals(TaskReviewStatus::Approved, $task->review_status);
    }

    public function test_user_can_request_adjustment_and_task_returns_to_pending(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
            'review_status' => TaskReviewStatus::PendingReview,
            'revision_count' => 0,
            'claimed_by_worker' => 'w-1',
        ]);

        $execution = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'worker_id' => 'w-1',
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('tasks.executions.reviews.store', [$task, $execution]), [
                'decision' => TaskReviewDecision::NeedsAdjustment->value,
                'notes' => 'Ajustar validação.',
                'current_behavior' => 'Aceita inválido',
                'expected_behavior' => 'Rejeitar',
                'preserve_scope' => 'API pública',
            ])
            ->assertRedirect(route('tasks.show', $task));

        $task->refresh();

        $this->assertEquals(TaskStatus::Pending, $task->status);
        $this->assertSame(1, $task->revision_count);
        $this->assertNull($task->claimed_by_worker);
    }

    public function test_review_validation_errors_on_web(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['is_active' => true]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Review,
        ]);

        $execution = TaskExecution::factory()->create([
            'task_id' => $task->id,
            'status' => TaskExecutionStatus::Review,
            'finished_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('tasks.show', $task))
            ->post(route('tasks.executions.reviews.store', [$task, $execution]), [
                'decision' => TaskReviewDecision::NeedsAdjustment->value,
                'notes' => '',
            ])
            ->assertRedirect(route('tasks.show', $task))
            ->assertSessionHasErrors('notes');
    }

    public function test_guest_cannot_post_review(): void
    {
        $task = Task::factory()->create();
        $execution = TaskExecution::factory()->create(['task_id' => $task->id]);

        $this->post(route('tasks.executions.reviews.store', [$task, $execution]), [
            'decision' => TaskReviewDecision::Approved->value,
        ])
            ->assertRedirect(route('login'));
    }
}
