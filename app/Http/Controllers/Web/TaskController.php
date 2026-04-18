<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\ProjectEnvironmentProfile;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Support\Enums\TaskExecutionStatus;
use App\Support\Enums\TaskStatus;
use App\Services\Task\CreateTaskService;
use App\Services\Task\UpdateTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
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

        $statusPresentations = $this->taskStatusPresentations();

        return view('tasks.index', compact('tasks', 'statusPresentations'));
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

        $statusPresentations = $this->taskStatusPresentations();

        return view('tasks.show', compact('task', 'reviewableExecution', 'statusPresentations'));
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

        $statusPresentations = $this->taskStatusPresentations();

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

        $statusPresentations = $this->taskStatusPresentations();

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
     * @return array<string, array{label: string, badge_classes: string}>
     */
    private function taskStatusPresentations(): array
    {
        return collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => [
                    'label' => $this->taskStatusLabel($status),
                    'badge_classes' => $this->taskStatusBadgeClasses($status),
                ],
            ])
            ->all();
    }

    private function taskStatusLabel(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Draft => 'Rascunho',
            TaskStatus::Pending => 'Pendente',
            TaskStatus::Claimed => 'Em fila',
            TaskStatus::Running => 'Em andamento',
            TaskStatus::Review => 'Em revisão',
            TaskStatus::NeedsAdjustment => 'Precisa de ajustes',
            TaskStatus::Done => 'Concluída',
            TaskStatus::Failed => 'Falhou',
            TaskStatus::Blocked => 'Bloqueada',
            TaskStatus::Cancelled => 'Cancelada',
        };
    }

    private function taskStatusBadgeClasses(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Draft => 'bg-slate-100 text-slate-700',
            TaskStatus::Pending => 'bg-amber-100 text-amber-800',
            TaskStatus::Claimed => 'bg-sky-100 text-sky-800',
            TaskStatus::Running => 'bg-blue-100 text-blue-800',
            TaskStatus::Review => 'bg-violet-100 text-violet-800',
            TaskStatus::NeedsAdjustment => 'bg-orange-100 text-orange-800',
            TaskStatus::Done => 'bg-emerald-100 text-emerald-800',
            TaskStatus::Failed => 'bg-rose-100 text-rose-800',
            TaskStatus::Blocked => 'bg-red-100 text-red-800',
            TaskStatus::Cancelled => 'bg-zinc-200 text-zinc-700',
        };
    }
}
