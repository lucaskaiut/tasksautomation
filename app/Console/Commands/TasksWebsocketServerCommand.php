<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use App\Support\Realtime\TaskRealtimeTokenService;
use App\Support\Realtime\TaskStreamPayloadFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RuntimeException;

class TasksWebsocketServerCommand extends Command
{
    protected $signature = 'tasks:websocket
        {--host= : Host do servidor WebSocket}
        {--port= : Porta do servidor WebSocket}
        {--bridge-host= : Host do canal interno}
        {--bridge-port= : Porta do canal interno}';

    protected $description = 'Inicia o servidor WebSocket para atualizações em tempo real das tarefas.';

    /**
     * @var array<int, array{socket: resource, user: User, subscriptions: array<int, array<string, mixed>>}>
     */
    private array $clients = [];

    public function __construct(
        private readonly TaskRealtimeTokenService $taskRealtimeTokenService,
        private readonly TaskStreamPayloadFactory $taskStreamPayloadFactory,
        private readonly TaskPolicy $taskPolicy,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $wsHost = (string) ($this->option('host') ?: config('tasks-realtime.websocket.host'));
        $wsPort = (int) ($this->option('port') ?: config('tasks-realtime.websocket.port'));
        $bridgeHost = (string) ($this->option('bridge-host') ?: config('tasks-realtime.bridge.host'));
        $bridgePort = (int) ($this->option('bridge-port') ?: config('tasks-realtime.bridge.port'));

        $websocketServer = $this->createServer($wsHost, $wsPort);
        $bridgeServer = $this->createServer($bridgeHost, $bridgePort);

        $this->info(sprintf(
            'Task WebSocket escutando em %s:%d e bridge interna em %s:%d',
            $wsHost,
            $wsPort,
            $bridgeHost,
            $bridgePort,
        ));

        while (true) {
            $readSockets = [$websocketServer, $bridgeServer];

            foreach ($this->clients as $client) {
                $readSockets[] = $client['socket'];
            }

            $changedStreams = $readSockets;
            $writeStreams = [];
            $exceptStreams = [];

            if (@stream_select($changedStreams, $writeStreams, $exceptStreams, 1) === false) {
                continue;
            }

            foreach ($changedStreams as $stream) {
                if ($stream === $websocketServer) {
                    $this->acceptWebsocketClient($websocketServer);

                    continue;
                }

                if ($stream === $bridgeServer) {
                    $this->broadcastBridgeMessage($bridgeServer);

                    continue;
                }

                $this->processClientFrame($stream);
            }
        }
    }

    /**
     * @return resource
     */
    private function createServer(string $host, int $port)
    {
        $server = @stream_socket_server(
            sprintf('tcp://%s:%d', $host, $port),
            $errorCode,
            $errorMessage
        );

        if (! is_resource($server)) {
            throw new RuntimeException(sprintf('Não foi possível iniciar o servidor em %s:%d (%s).', $host, $port, $errorMessage));
        }

        stream_set_blocking($server, false);

        return $server;
    }

    /**
     * @param  resource  $server
     */
    private function acceptWebsocketClient($server): void
    {
        $socket = @stream_socket_accept($server, 0);

        if (! is_resource($socket)) {
            return;
        }

        $request = '';

        while (! str_contains($request, "\r\n\r\n")) {
            $chunk = fread($socket, 2048);

            if ($chunk === false || $chunk === '') {
                fclose($socket);

                return;
            }

            $request .= $chunk;
        }

        $token = $this->extractTokenFromHandshake($request);
        $user = $this->taskRealtimeTokenService->resolveUser($token);

        if (! $user instanceof User) {
            fwrite($socket, "HTTP/1.1 401 Unauthorized\r\nConnection: close\r\n\r\n");
            fclose($socket);

            return;
        }

        $websocketKey = $this->extractHeader($request, 'Sec-WebSocket-Key');

        if ($websocketKey === null) {
            fwrite($socket, "HTTP/1.1 400 Bad Request\r\nConnection: close\r\n\r\n");
            fclose($socket);

            return;
        }

        fwrite($socket, $this->upgradeResponse($websocketKey));
        stream_set_blocking($socket, false);

        $this->clients[(int) $socket] = [
            'socket' => $socket,
            'user' => $user,
            'subscriptions' => [],
        ];

        $this->sendJson($socket, [
            'type' => 'connection.ready',
            'connected_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * @param  resource  $bridgeServer
     */
    private function broadcastBridgeMessage($bridgeServer): void
    {
        $socket = @stream_socket_accept($bridgeServer, 0);

        if (! is_resource($socket)) {
            return;
        }

        $payload = trim(stream_get_contents($socket) ?: '');
        fclose($socket);

        if ($payload === '') {
            return;
        }

        /** @var array<string, mixed>|null $message */
        $message = json_decode($payload, true);

        if (! is_array($message)) {
            return;
        }

        foreach ($this->clients as $client) {
            if ($this->clientShouldReceive($client, $message)) {
                $this->sendJson($client['socket'], $message);
            }
        }
    }

    /**
     * @param  resource  $stream
     */
    private function processClientFrame($stream): void
    {
        $clientId = (int) $stream;

        if (! isset($this->clients[$clientId])) {
            return;
        }

        $frame = $this->readFrame($stream);

        if ($frame === null) {
            $this->disconnect($clientId);

            return;
        }

        if ($frame['opcode'] === 0x8) {
            $this->disconnect($clientId);

            return;
        }

        if ($frame['opcode'] === 0x9) {
            $this->writeFrame($stream, $frame['payload'], 0xA);

            return;
        }

        if ($frame['opcode'] !== 0x1) {
            return;
        }

        /** @var array<string, mixed>|null $message */
        $message = json_decode($frame['payload'], true);

        if (! is_array($message)) {
            return;
        }

        match ((string) Arr::get($message, 'type')) {
            'subscribe' => $this->handleSubscribe($clientId, $message),
            'unsubscribe' => $this->handleUnsubscribe($clientId, $message),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleSubscribe(int $clientId, array $message): void
    {
        $client = $this->clients[$clientId];
        $subscriptions = Arr::wrap($message['subscriptions'] ?? []);
        $snapshots = [];

        foreach ($subscriptions as $subscription) {
            if (! is_array($subscription)) {
                continue;
            }

            $normalized = $this->normalizeSubscription($client['user'], $subscription);

            if ($normalized === null) {
                continue;
            }

            if (! in_array($normalized, $this->clients[$clientId]['subscriptions'], true)) {
                $this->clients[$clientId]['subscriptions'][] = $normalized;
            }

            $snapshots = array_merge($snapshots, $this->snapshotsForSubscription($normalized));
        }

        $this->sendJson($client['socket'], [
            'type' => 'subscription.synced',
            'tasks' => array_values($this->uniqueSnapshots($snapshots)),
            'synced_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleUnsubscribe(int $clientId, array $message): void
    {
        $subscriptions = array_filter(
            Arr::wrap($message['subscriptions'] ?? []),
            fn (mixed $subscription): bool => is_array($subscription)
        );

        $this->clients[$clientId]['subscriptions'] = array_values(array_filter(
            $this->clients[$clientId]['subscriptions'],
            fn (array $existing): bool => ! in_array($existing, $subscriptions, true)
        ));
    }

    /**
     * @param  array{socket: resource, user: User, subscriptions: array<int, array<string, mixed>>}  $client
     * @param  array<string, mixed>  $message
     */
    private function clientShouldReceive(array $client, array $message): bool
    {
        foreach ($client['subscriptions'] as $subscription) {
            if ($this->subscriptionMatchesMessage($client['user'], $subscription, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $subscription
     * @param  array<string, mixed>  $message
     */
    private function subscriptionMatchesMessage(User $user, array $subscription, array $message): bool
    {
        $taskId = (int) ($message['task_id'] ?? 0);

        if ($taskId <= 0 || ! $this->userCanReceiveMessage($user, $message)) {
            return false;
        }

        return match ($subscription['scope']) {
            'task' => (int) $subscription['task_id'] === $taskId,
            'list' => in_array($taskId, $subscription['task_ids'], true),
            'index' => true,
            'project' => (int) $subscription['project_id'] === (int) $message['project_id'],
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $subscription
     * @return array<string, mixed>|null
     */
    private function normalizeSubscription(User $user, array $subscription): ?array
    {
        $scope = (string) ($subscription['scope'] ?? '');

        if ($scope === 'task') {
            $task = Task::query()
                ->with(['project', 'environmentProfile', 'lastReviewer'])
                ->find((int) ($subscription['task_id'] ?? 0));

            if (! $task instanceof Task || ! $this->taskPolicy->view($user, $task)) {
                return null;
            }

            return [
                'scope' => 'task',
                'task_id' => $task->id,
            ];
        }

        if ($scope === 'list') {
            $taskIds = array_values(array_unique(array_map('intval', Arr::wrap($subscription['task_ids'] ?? []))));

            if ($taskIds === []) {
                return null;
            }

            $allowedIds = Task::query()
                ->whereIn('id', $taskIds)
                ->get()
                ->filter(fn (Task $task): bool => $this->taskPolicy->view($user, $task))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

            if ($allowedIds === []) {
                return null;
            }

            return [
                'scope' => 'list',
                'task_ids' => $allowedIds,
            ];
        }

        if ($scope === 'index') {
            if (! $this->taskPolicy->viewAny($user)) {
                return null;
            }

            return [
                'scope' => 'index',
                'page' => max(1, (int) ($subscription['page'] ?? 1)),
                'per_page' => min(100, max(1, (int) ($subscription['per_page'] ?? 20))),
            ];
        }

        if ($scope === 'project') {
            if (! $this->taskPolicy->viewAny($user)) {
                return null;
            }

            $project = Project::query()->find((int) ($subscription['project_id'] ?? 0));

            if (! $project instanceof Project) {
                return null;
            }

            return [
                'scope' => 'project',
                'project_id' => $project->id,
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $subscription
     * @return array<int, array<string, mixed>>
     */
    private function snapshotsForSubscription(array $subscription): array
    {
        $query = Task::query()->with(['project', 'environmentProfile', 'lastReviewer', 'creator']);

        match ($subscription['scope']) {
            'task' => $query->whereKey($subscription['task_id']),
            'list' => $query->whereIn('id', $subscription['task_ids']),
            'index' => $query->latest()->forPage($subscription['page'], $subscription['per_page']),
            'project' => $query->where('project_id', $subscription['project_id']),
            default => null,
        };

        return $query->get()
            ->map(fn (Task $task): array => $this->taskStreamPayloadFactory->make(
                type: 'task.snapshot',
                task: $task,
                occurredAt: $task->updated_at ?? Carbon::now(),
            ))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function userCanReceiveMessage(User $user, array $message): bool
    {
        if (($message['type'] ?? null) === 'task.deleted') {
            return $this->taskPolicy->viewAny($user);
        }

        $task = Task::query()->find((int) ($message['task_id'] ?? 0));

        return $task instanceof Task && $this->taskPolicy->view($user, $task);
    }

    /**
     * @param  array<int, array<string, mixed>>  $snapshots
     * @return array<int, array<string, mixed>>
     */
    private function uniqueSnapshots(array $snapshots): array
    {
        $unique = [];

        foreach ($snapshots as $snapshot) {
            $taskId = (int) ($snapshot['task_id'] ?? 0);

            if ($taskId > 0) {
                $unique[$taskId] = $snapshot;
            }
        }

        return $unique;
    }

    private function disconnect(int $clientId): void
    {
        $client = $this->clients[$clientId] ?? null;

        if ($client !== null && is_resource($client['socket'])) {
            fclose($client['socket']);
        }

        unset($this->clients[$clientId]);
    }

    private function extractTokenFromHandshake(string $request): ?string
    {
        $firstLine = strtok($request, "\r\n");

        if (! is_string($firstLine)) {
            return null;
        }

        preg_match('#GET\s+([^\s]+)\s+HTTP#', $firstLine, $matches);

        $path = $matches[1] ?? null;

        if (! is_string($path)) {
            return null;
        }

        $queryString = parse_url($path, PHP_URL_QUERY);

        if (! is_string($queryString)) {
            return null;
        }

        parse_str($queryString, $query);

        return is_string($query['token'] ?? null) ? $query['token'] : null;
    }

    private function extractHeader(string $request, string $name): ?string
    {
        foreach (explode("\r\n", $request) as $line) {
            if (str_starts_with(strtolower($line), strtolower($name).':')) {
                return trim(substr($line, strlen($name) + 1));
            }
        }

        return null;
    }

    private function upgradeResponse(string $websocketKey): string
    {
        $accept = base64_encode(sha1(trim($websocketKey).'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        return implode("\r\n", [
            'HTTP/1.1 101 Switching Protocols',
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Accept: '.$accept,
            '',
            '',
        ]);
    }

    /**
     * @param  resource  $stream
     * @return array{opcode: int, payload: string}|null
     */
    private function readFrame($stream): ?array
    {
        $header = fread($stream, 2);

        if ($header === '' || $header === false || strlen($header) < 2) {
            return null;
        }

        $firstByte = ord($header[0]);
        $secondByte = ord($header[1]);
        $opcode = $firstByte & 0x0F;
        $isMasked = ($secondByte & 0x80) === 0x80;
        $payloadLength = $secondByte & 0x7F;

        if ($payloadLength === 126) {
            $extended = fread($stream, 2);
            $payloadLength = unpack('n', $extended)[1];
        } elseif ($payloadLength === 127) {
            $extended = fread($stream, 8);
            $unpacked = unpack('N2', $extended);
            $payloadLength = ((int) $unpacked[1] << 32) | (int) $unpacked[2];
        }

        $mask = $isMasked ? fread($stream, 4) : '';
        $payload = $payloadLength > 0 ? fread($stream, $payloadLength) : '';

        if ($payload === false) {
            return null;
        }

        if ($isMasked) {
            $decoded = '';

            for ($index = 0; $index < $payloadLength; $index++) {
                $decoded .= $payload[$index] ^ $mask[$index % 4];
            }

            $payload = $decoded;
        }

        return [
            'opcode' => $opcode,
            'payload' => $payload,
        ];
    }

    /**
     * @param  resource  $stream
     * @param  array<string, mixed>  $payload
     */
    private function sendJson($stream, array $payload): void
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->writeFrame($stream, $json, 0x1);
    }

    /**
     * @param  resource  $stream
     */
    private function writeFrame($stream, string $payload, int $opcode): void
    {
        $length = strlen($payload);
        $frame = chr(0x80 | $opcode);

        if ($length <= 125) {
            $frame .= chr($length);
        } elseif ($length <= 65535) {
            $frame .= chr(126).pack('n', $length);
        } else {
            $frame .= chr(127).pack('NN', ($length >> 32) & 0xFFFFFFFF, $length & 0xFFFFFFFF);
        }

        fwrite($stream, $frame.$payload);
    }
}
