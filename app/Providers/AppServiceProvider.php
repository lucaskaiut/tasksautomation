<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Observers\TaskObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Task::observe(TaskObserver::class);

        Route::bind('taskExecution', function (string $value, $route) {
            $task = $route->parameter('task');
            if ($task instanceof Task) {
                return TaskExecution::query()
                    ->where('task_id', $task->id)
                    ->whereKey($value)
                    ->firstOrFail();
            }

            return TaskExecution::query()->whereKey($value)->firstOrFail();
        });
    }
}
