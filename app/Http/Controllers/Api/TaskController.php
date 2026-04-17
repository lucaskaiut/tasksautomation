<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\ClaimTaskRequest;
use App\Http\Requests\Task\FinishTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\TaskHeartbeatRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\Task\ClaimTaskService;
use App\Services\Task\CreateTaskService;
use App\Services\Task\FinishTaskService;
use App\Services\Task\TaskHeartbeatService;
use App\Services\Task\UpdateTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->with(['project', 'environmentProfile', 'lastReviewer'])
            ->latest()
            ->paginate(20);

        return TaskResource::collection($tasks)
            ->additional(['message' => 'Lista de tarefas.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, CreateTaskService $service): JsonResponse
    {
        $task = $service->handle($request->taskData(), $request->user());

        $task->load(['project', 'environmentProfile', 'lastReviewer']);

        return (new TaskResource($task))
            ->additional(['message' => 'Tarefa criada com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        $task->load(['project', 'environmentProfile', 'lastReviewer']);

        return (new TaskResource($task))
            ->additional(['message' => 'Tarefa.']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskService $service): TaskResource
    {
        $task = $service->handle($task, $request->taskData());
        $task->load(['project', 'environmentProfile', 'lastReviewer']);

        return (new TaskResource($task))
            ->additional(['message' => 'Tarefa atualizada com sucesso.']);
    }

    public function claim(ClaimTaskRequest $request, ClaimTaskService $service): JsonResponse|\Illuminate\Http\Response
    {
        $task = $service->handle($request->string('worker_id')->toString());

        if ($task === null) {
            return response()
                ->noContent();
        }

        $task->load(['project', 'environmentProfile', 'lastReviewer']);

        return (new TaskResource($task))
            ->additional(['message' => 'Tarefa claimada com sucesso.'])
            ->response()
            ->setStatusCode(200);
    }

    public function heartbeat(TaskHeartbeatRequest $request, Task $task, TaskHeartbeatService $service): TaskResource
    {
        $task = $service->handle($task, $request->string('worker_id')->toString());
        $task->load(['project', 'environmentProfile', 'lastReviewer']);

        return (new TaskResource($task))
            ->additional(['message' => 'Heartbeat registrado com sucesso.']);
    }

    public function finish(FinishTaskRequest $request, Task $task, FinishTaskService $service): TaskResource
    {
        $task = $service->handle(
            task: $task,
            workerId: $request->string('worker_id')->toString(),
            requestedStatus: \App\Support\Enums\TaskStatus::from($request->string('status')->toString()),
            executionSummary: $request->string('execution_summary')->toString() ?: null,
            failureReason: $request->string('failure_reason')->toString() ?: null,
            branchName: $request->string('branch_name')->toString() ?: null,
            commitSha: $request->string('commit_sha')->toString() ?: null,
            pullRequestUrl: $request->string('pull_request_url')->toString() ?: null,
            logsPath: $request->string('logs_path')->toString() ?: null,
            metadata: $request->input('metadata'),
        );

        $task->load(['project', 'environmentProfile', 'lastReviewer']);

        return (new TaskResource($task))
            ->additional(['message' => 'Tarefa finalizada com sucesso.']);
    }
}
