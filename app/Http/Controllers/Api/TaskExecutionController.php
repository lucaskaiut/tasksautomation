<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskExecutionResource;
use App\Models\Task;
use App\Models\TaskExecution;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskExecutionController extends Controller
{
    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        $executions = $task->executions()
            ->with(['review.author'])
            ->orderByDesc('id')
            ->get();

        return TaskExecutionResource::collection($executions)
            ->additional(['message' => 'Execuções da tarefa.']);
    }

    public function show(TaskExecution $taskExecution): TaskExecutionResource
    {
        $this->authorize('view', $taskExecution->task);

        $taskExecution->load(['task', 'review.author']);

        return (new TaskExecutionResource($taskExecution))
            ->additional(['message' => 'Execução da tarefa.']);
    }
}
