<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Throwable;

class ApplicationHealthChecker
{
    /**
     * @return array{healthy: bool, checks: array<string, array<string, mixed>>}
     */
    public function run(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'session' => $this->checkSession(),
            'api_routes' => $this->checkApiRoutes(),
            'web_routes' => $this->checkWebRoutes(),
            'web_views' => $this->checkWebViews(),
        ];

        if ($this->applicationRequiresRedis()) {
            $checks['redis'] = $this->checkRedis();
        } else {
            $checks['redis'] = [
                'status' => 'skipped',
                'reason' => 'Queue, cache and session are not using Redis.',
            ];
        }

        $healthy = collect($checks)->every(function (array $check): bool {
            if (($check['status'] ?? '') === 'skipped') {
                return true;
            }

            return ($check['status'] ?? '') === 'pass';
        });

        return [
            'healthy' => $healthy,
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        $start = hrtime(true);

        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $exception) {
            $this->reportSafely($exception);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkCache(): array
    {
        $start = hrtime(true);
        $key = 'health:'.Str::uuid()->toString();

        try {
            Cache::put($key, true, 5);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== true) {
                return [
                    'status' => 'fail',
                    'latency_ms' => $this->elapsedMs($start),
                    'message' => 'Cache read did not match written value.',
                ];
            }

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $exception) {
            $this->reportSafely($exception);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSession(): array
    {
        $start = hrtime(true);
        $key = 'health_'.Str::uuid()->toString();

        try {
            $store = Session::driver();
            $store->put($key, true);
            $value = $store->get($key);
            $store->forget($key);

            if ($value !== true) {
                return [
                    'status' => 'fail',
                    'latency_ms' => $this->elapsedMs($start),
                    'driver' => config('session.driver'),
                    'message' => 'Session read did not match written value.',
                ];
            }

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
                'driver' => config('session.driver'),
            ];
        } catch (Throwable $exception) {
            $this->reportSafely($exception);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'driver' => config('session.driver'),
                'message' => $this->safeMessage($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkApiRoutes(): array
    {
        $start = hrtime(true);
        $requiredRoutes = [
            'api.health',
            'api.tokens.create',
            'api.projects.index',
            'api.tasks.index',
        ];

        try {
            $missingRoutes = collect($requiredRoutes)
                ->reject(static fn (string $routeName): bool => Route::has($routeName))
                ->values()
                ->all();

            if ($missingRoutes !== []) {
                return [
                    'status' => 'fail',
                    'latency_ms' => $this->elapsedMs($start),
                    'missing_routes' => $missingRoutes,
                ];
            }

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $exception) {
            $this->reportSafely($exception);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWebRoutes(): array
    {
        $start = hrtime(true);
        $requiredRoutes = [
            'login',
            'projects.index',
            'tasks.index',
            'profile.edit',
        ];

        try {
            $missingRoutes = collect($requiredRoutes)
                ->reject(static fn (string $routeName): bool => Route::has($routeName))
                ->values()
                ->all();

            if ($missingRoutes !== []) {
                return [
                    'status' => 'fail',
                    'latency_ms' => $this->elapsedMs($start),
                    'missing_routes' => $missingRoutes,
                ];
            }

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $exception) {
            $this->reportSafely($exception);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWebViews(): array
    {
        $start = hrtime(true);
        $requiredViews = [
            'welcome',
            'auth.login',
        ];

        try {
            foreach ($requiredViews as $viewName) {
                View::make($viewName)->render();
            }

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
                'views' => $requiredViews,
            ];
        } catch (Throwable $exception) {
            $this->reportSafely($exception);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($exception),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkRedis(): array
    {
        $start = hrtime(true);

        try {
            Redis::connection()->ping();

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $exception) {
            $this->reportSafely($exception);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($exception),
            ];
        }
    }

    private function applicationRequiresRedis(): bool
    {
        if (config('queue.default') === 'redis') {
            return true;
        }

        if (config('session.driver') === 'redis') {
            return true;
        }

        $store = config('cache.default');

        return is_string($store)
            && (config("cache.stores.{$store}.driver") ?? null) === 'redis';
    }

    private function elapsedMs(float $startHrTime): float
    {
        return round((hrtime(true) - $startHrTime) / 1_000_000, 2);
    }

    private function safeMessage(Throwable $exception): string
    {
        if (config('app.debug')) {
            return $exception->getMessage();
        }

        return 'Check failed.';
    }

    private function reportSafely(Throwable $exception): void
    {
        try {
            report($exception);
        } catch (Throwable) {
            //
        }
    }
}
