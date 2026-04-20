<?php

namespace App\Http\Controllers\Api\Realtime;

use App\Http\Controllers\Controller;
use App\Support\Realtime\TaskRealtimeTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskWebsocketTokenController extends Controller
{
    public function __invoke(Request $request, TaskRealtimeTokenService $taskRealtimeTokenService): JsonResponse
    {
        $user = $request->user();
        $token = $taskRealtimeTokenService->issue($user);
        $ttlSeconds = (int) config('tasks-realtime.auth.token_ttl_seconds');
        $path = (string) config('tasks-realtime.websocket.path');
        $publicOrigin = config('tasks-realtime.client.public_ws_origin');
        $websocketUrl = self::composeWebSocketUrl($token, $path, $publicOrigin);

        return response()->json([
            'data' => [
                'token' => $token,
                'expires_in_seconds' => $ttlSeconds,
                'websocket_path' => $path,
                'websocket_url' => $websocketUrl,
            ],
            'message' => 'Token WebSocket emitido com sucesso.',
        ]);
    }

    private static function composeWebSocketUrl(string $token, string $path, mixed $publicOrigin): ?string
    {
        if (! is_string($publicOrigin) || $publicOrigin === '') {
            return null;
        }

        $base = rtrim($publicOrigin, '/');

        return $base.$path.'?'.http_build_query(['token' => $token]);
    }
}
