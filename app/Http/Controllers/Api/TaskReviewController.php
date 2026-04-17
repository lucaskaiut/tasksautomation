<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskReviewRequest;
use App\Http\Resources\TaskReviewResource;
use App\Models\TaskExecution;
use App\Services\Task\SubmitTaskReviewService;
use App\Support\Enums\TaskReviewDecision;
use Illuminate\Http\JsonResponse;

class TaskReviewController extends Controller
{
    public function store(
        StoreTaskReviewRequest $request,
        TaskExecution $taskExecution,
        SubmitTaskReviewService $service,
    ): JsonResponse {
        $review = $service->handle(
            $taskExecution->task,
            $taskExecution,
            $request->user(),
            TaskReviewDecision::from($request->validated('decision')),
            trim((string) ($request->validated('notes') ?? '')),
            $request->validated('current_behavior') ?: null,
            $request->validated('expected_behavior') ?: null,
            $request->validated('preserve_scope') ?: null,
        );

        $review->load('author');

        return (new TaskReviewResource($review))
            ->additional(['message' => 'Revisão registrada com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }
}
