<?php

namespace Tests\Feature\Api\Health;

use App\Services\Health\ApplicationHealthChecker;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_returns_healthy_response_when_checker_passes(): void
    {
        $this->mock(ApplicationHealthChecker::class, function ($mock): void {
            $mock->shouldReceive('run')->once()->andReturn([
                'healthy' => true,
                'checks' => [
                    'database' => ['status' => 'pass', 'latency_ms' => 1.2],
                    'cache' => ['status' => 'pass', 'latency_ms' => 0.5],
                    'session' => ['status' => 'pass', 'latency_ms' => 0.3, 'driver' => 'array'],
                    'api_routes' => ['status' => 'pass', 'latency_ms' => 0.1],
                    'web_routes' => ['status' => 'pass', 'latency_ms' => 0.1],
                    'web_views' => ['status' => 'pass', 'latency_ms' => 2.5, 'views' => ['welcome', 'auth.login']],
                    'redis' => ['status' => 'skipped', 'reason' => 'Queue, cache and session are not using Redis.'],
                ],
            ]);
        });

        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonPath('status', 'healthy');
        $response->assertJsonStructure([
            'status',
            'checks' => [
                'database' => ['status', 'latency_ms'],
                'cache' => ['status', 'latency_ms'],
                'session' => ['status', 'latency_ms', 'driver'],
                'api_routes' => ['status', 'latency_ms'],
                'web_routes' => ['status', 'latency_ms'],
                'web_views' => ['status', 'latency_ms', 'views'],
                'redis',
            ],
        ]);

        $this->assertSame('pass', $response->json('checks.database.status'));
        $this->assertSame('pass', $response->json('checks.cache.status'));
        $this->assertSame('pass', $response->json('checks.session.status'));
        $this->assertSame('pass', $response->json('checks.api_routes.status'));
        $this->assertSame('pass', $response->json('checks.web_routes.status'));
        $this->assertSame('pass', $response->json('checks.web_views.status'));
    }

    public function test_health_returns_service_unavailable_when_checker_fails(): void
    {
        $this->mock(ApplicationHealthChecker::class, function ($mock): void {
            $mock->shouldReceive('run')->once()->andReturn([
                'healthy' => false,
                'checks' => [
                    'database' => ['status' => 'fail', 'latency_ms' => 4.2, 'message' => 'Check failed.'],
                ],
            ]);
        });

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.database.status', 'fail');
    }
}
