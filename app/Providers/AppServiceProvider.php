<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Observers\TaskObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('task-realtime-ws-token', function (Request $request): Limit {
            return Limit::perMinute(30)->by((string) $request->user()->getAuthIdentifier());
        });

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
