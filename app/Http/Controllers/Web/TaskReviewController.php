<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskReviewRequest;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Services\Task\SubmitTaskReviewService;
use App\Support\Enums\TaskReviewDecision;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TaskReviewController extends Controller
{
    public function store(
        StoreTaskReviewRequest $request,
        Task $task,
        TaskExecution $taskExecution,
        SubmitTaskReviewService $service,
    ): RedirectResponse {
        try {
            $service->handle(
                $task,
                $taskExecution,
                $request->user(),
                TaskReviewDecision::from($request->validated('decision')),
                trim((string) ($request->validated('notes') ?? '')),
                $request->validated('current_behavior') ?: null,
                $request->validated('expected_behavior') ?: null,
                $request->validated('preserve_scope') ?: null,
            );
        } catch (ConflictHttpException $e) {
            return redirect()
                ->route('tasks.show', $task)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Revisão registrada com sucesso.');
    }
}
