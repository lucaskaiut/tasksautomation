<?php

use App\Http\Controllers\Api\Auth\TokenController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\Realtime\TaskWebsocketTokenController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskExecutionController;
use App\Http\Controllers\Api\TaskReviewController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::get('health', HealthController::class)->name('health');
    Route::post('/tokens/create', [TokenController::class, 'store'])->name('tokens.create');

    Route::middleware(['auth:sanctum', 'throttle:task-realtime-ws-token'])->group(function () {
        Route::get('realtime/tasks/ws-token', TaskWebsocketTokenController::class)->name('realtime.tasks.ws-token');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('projects', ProjectController::class)->only([
            'index', 'store', 'show', 'update',
        ]);

        Route::apiResource('tasks', TaskController::class)->only([
            'index', 'store', 'show', 'update',
        ]);

        Route::post('tasks/claim', [TaskController::class, 'claim'])->name('tasks.claim');
        Route::post('tasks/{task}/heartbeat', [TaskController::class, 'heartbeat'])->name('tasks.heartbeat');
        Route::post('tasks/{task}/finish', [TaskController::class, 'finish'])->name('tasks.finish');

        Route::get('tasks/{task}/executions', [TaskExecutionController::class, 'index'])->name('tasks.executions.index');
        Route::get('task-executions/{taskExecution}', [TaskExecutionController::class, 'show'])->name('task-executions.show');
        Route::post('task-executions/{taskExecution}/reviews', [TaskReviewController::class, 'store'])->name('task-executions.reviews.store');
    });
});
