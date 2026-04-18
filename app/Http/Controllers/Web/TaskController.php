<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Support\Enums\TaskReviewStatus;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Realtime\TaskRealtimeTokenService;
use App\Support\TaskStatusPresenter;
use App\Services\Task\CreateTaskService;
use App\Services\Task\UpdateTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskStatusPresenter $taskStatusPresenter,
        private readonly TaskRealtimeTokenService $taskRealtimeTokenService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->with(['project', 'creator', 'environmentProfile', 'lastReviewer'])
            ->latest()
            ->paginate(20);

        $statusPresentations = $this->taskStatusPresenter->presentations();
        $realtimeConfig = $this->realtimeConfig(
            subscriptions: [[
                'scope' => 'list',
                'task_ids' => $tasks->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            ]],
        );

        $reviewStatusPresentations = $this->taskReviewStatusPresentations();

        return view('tasks.index', compact('tasks', 'statusPresentations', 'reviewStatusPresentations', 'realtimeConfig'));
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load([
            'project',
            'environmentProfile',
            'creator',
            'lastReviewer',
            'executions' => fn ($query) => $query->with(['review.author'])->orderByDesc('id'),
            'reviews' => fn ($query) => $query->with(['author', 'taskExecution'])->orderByDesc('id'),
        ]);

        $reviewableExecution = TaskExecution::query()
            ->where('task_id', $task->id)
            ->where('status', TaskExecutionStatus::Review)
            ->whereNotNull('finished_at')
            ->whereDoesntHave('review')
            ->orderByDesc('id')
            ->first();

        $statusPresentations = $this->taskStatusPresenter->presentations();
        $realtimeConfig = $this->realtimeConfig(
            subscriptions: [[
                'scope' => 'task',
                'task_id' => $task->id,
            ]],
        );

        $reviewStatusPresentations = $this->taskReviewStatusPresentations();

        return view('tasks.show', compact('task', 'reviewableExecution', 'statusPresentations', 'reviewStatusPresentations', 'realtimeConfig'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Task::class);

        $projects = Project::query()->orderBy('name')->get();
        $environmentProfiles = ProjectEnvironmentProfile::query()
            ->with('project')
            ->orderBy('project_id')
            ->orderBy('name')
            ->get();

        $statusPresentations = $this->taskStatusPresenter->presentations();

        return view('tasks.create', compact('projects', 'environmentProfiles', 'statusPresentations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, CreateTaskService $service): RedirectResponse
    {
        $service->handle($request->taskData(), $request->user());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tarefa criada com sucesso.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        $projects = Project::query()->orderBy('name')->get();
        $environmentProfiles = ProjectEnvironmentProfile::query()
            ->with('project')
            ->orderBy('project_id')
            ->orderBy('name')
            ->get();

        $statusPresentations = $this->taskStatusPresenter->presentations();

        return view('tasks.edit', compact('task', 'projects', 'environmentProfiles', 'statusPresentations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskService $service): RedirectResponse
    {
        $service->handle($task, $request->taskData());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Tarefa atualizada com sucesso.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $subscriptions
     * @return array<string, mixed>
     */
    private function realtimeConfig(array $subscriptions): array
    {
        return [
            'path' => (string) config('tasks-realtime.websocket.path'),
            'token' => $this->taskRealtimeTokenService->issue(auth()->user()),
            'subscriptions' => $subscriptions,
            'statusPresentations' => $this->taskStatusPresenter->presentations(),
        ];
    }

    /**
     * @return array<string, array{label: string, badge_classes: string}>
     */
    private function taskReviewStatusPresentations(): array
    {
        return collect(TaskReviewStatus::cases())
            ->mapWithKeys(fn (TaskReviewStatus $status): array => [
                $status->value => [
                    'label' => $this->taskReviewStatusLabel($status),
                    'badge_classes' => $this->taskReviewStatusBadgeClasses($status),
                ],
            ])
            ->all();
    }

    private function taskReviewStatusLabel(TaskReviewStatus $status): string
    {
        return match ($status) {
            TaskReviewStatus::PendingReview => 'Aguardando revisão',
            TaskReviewStatus::Approved => 'Aprovada',
            TaskReviewStatus::NeedsAdjustment => 'Precisa de ajustes',
        };
    }

    private function taskReviewStatusBadgeClasses(TaskReviewStatus $status): string
    {
        return match ($status) {
            TaskReviewStatus::PendingReview => 'bg-violet-100 text-violet-800',
            TaskReviewStatus::Approved => 'bg-emerald-100 text-emerald-800',
            TaskReviewStatus::NeedsAdjustment => 'bg-orange-100 text-orange-800',
        };
    }
}
