<?php

namespace Tests\Feature\Api\Realtime;

use App\Models\User;
use App\Support\Realtime\TaskRealtimeTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TaskWebsocketTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_request_websocket_token(): void
    {
        $this->getJson('/api/realtime/tasks/ws-token')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_receives_valid_token_payload(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken('test')->plainTextToken;
        $ttl = (int) config('tasks-realtime.auth.token_ttl_seconds');

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/realtime/tasks/ws-token')
            ->assertOk()
            ->assertJsonPath('data.expires_in_seconds', $ttl)
            ->assertJsonPath('data.websocket_path', config('tasks-realtime.websocket.path'))
            ->assertJsonPath('data.websocket_url', null)
            ->assertJsonStructure([
                'data' => ['token', 'expires_in_seconds', 'websocket_path', 'websocket_url'],
                'message',
            ]);

        $token = $response->json('data.token');
        $this->assertIsString($token);

        $resolved = app(TaskRealtimeTokenService::class)->resolveUser($token);
        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($user));
    }

    public function test_websocket_url_is_built_when_public_origin_is_configured(): void
    {
        Config::set('tasks-realtime.client.public_ws_origin', 'wss://rt.example.com');
        Config::set('tasks-realtime.websocket.path', '/ws/tasks');

        $user = User::factory()->create();
        $plainToken = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/realtime/tasks/ws-token')
            ->assertOk();

        $wsUrl = $response->json('data.websocket_url');
        $this->assertIsString($wsUrl);
        $this->assertStringStartsWith('wss://rt.example.com/ws/tasks?token=', $wsUrl);

        $query = parse_url($wsUrl, PHP_URL_QUERY);
        $this->assertIsString($query);
        parse_str($query, $params);
        $this->assertArrayHasKey('token', $params);

        $resolved = app(TaskRealtimeTokenService::class)->resolveUser($params['token']);
        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($user));
    }
}
