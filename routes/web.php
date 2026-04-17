<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\ProjectEnvironmentProfileController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\Web\TaskReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('projects.index')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('projects', ProjectController::class)->only([
        'index', 'create', 'store', 'edit', 'update',
    ]);

    Route::get('projects/{project}/environment-profiles', [ProjectEnvironmentProfileController::class, 'index'])
        ->name('projects.environment-profiles.index');
    Route::post('projects/{project}/environment-profiles', [ProjectEnvironmentProfileController::class, 'store'])
        ->name('projects.environment-profiles.store');
    Route::get('projects/{project}/environment-profiles/{environmentProfile}/edit', [ProjectEnvironmentProfileController::class, 'edit'])
        ->name('projects.environment-profiles.edit');
    Route::put('projects/{project}/environment-profiles/{environmentProfile}', [ProjectEnvironmentProfileController::class, 'update'])
        ->name('projects.environment-profiles.update');

    Route::resource('tasks', TaskController::class)->only([
        'index', 'create', 'store', 'show', 'edit', 'update',
    ]);

    Route::post('tasks/{task}/executions/{taskExecution}/reviews', [TaskReviewController::class, 'store'])
        ->name('tasks.executions.reviews.store');
});

require __DIR__.'/auth.php';
