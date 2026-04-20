<?php

return [
    'websocket' => [
        'host' => env('TASKS_REALTIME_WS_HOST', '0.0.0.0'),
        'port' => (int) env('TASKS_REALTIME_WS_PORT', 8081),
        'path' => env('TASKS_REALTIME_WS_PATH', '/ws/tasks'),
    ],
    'bridge' => [
        'host' => env('TASKS_REALTIME_BRIDGE_HOST', '127.0.0.1'),
        'port' => (int) env('TASKS_REALTIME_BRIDGE_PORT', 8082),
    ],
    'auth' => [
        'token_ttl_seconds' => (int) env('TASKS_REALTIME_TOKEN_TTL_SECONDS', 28800),
    ],
    'client' => [
        'public_ws_origin' => env('TASKS_REALTIME_PUBLIC_WS_ORIGIN'),
    ],
];
