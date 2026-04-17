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

        return view('tasks.index', compact('tasks'));
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

        return view('tasks.show', compact('task', 'reviewableExecution'));
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

        return view('tasks.create', compact('projects', 'environmentProfiles'));
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

        return view('tasks.edit', compact('task', 'projects', 'environmentProfiles'));
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
}
