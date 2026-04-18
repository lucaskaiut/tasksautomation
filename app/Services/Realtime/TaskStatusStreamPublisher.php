<?php

namespace App\Services\Realtime;

use App\Models\Task;
use App\Support\Realtime\TaskStatusPayloadFactory;
use Illuminate\Support\Carbon;

class TaskStatusStreamPublisher
{
    public function __construct(
        private readonly TaskStatusPayloadFactory $payloadFactory,
    ) {}

    public function publishStatusChange(Task $task, ?string $previousStatus): void
    {
        $currentStatus = $task->status?->value ?? (string) $task->status;

        if ($currentStatus === $previousStatus) {
            return;
        }

        $payload = $this->payloadFactory->make(
            task: $task->fresh(['project', 'environmentProfile', 'lastReviewer']) ?? $task,
            previousStatus: $previousStatus,
            changedAt: $task->updated_at ?? Carbon::now(),
        );

        $bridgeHost = (string) config('tasks-realtime.bridge.host');
        $bridgePort = (int) config('tasks-realtime.bridge.port');

        try {
            $socket = @stream_socket_client(
                sprintf('tcp://%s:%d', $bridgeHost, $bridgePort),
                $errorCode,
                $errorMessage,
                0.25
            );

            if (! is_resource($socket)) {
                return;
            }

            fwrite($socket, json_encode($payload, JSON_THROW_ON_ERROR)."\n");
            fclose($socket);
        } catch (\Throwable) {
            // The application must keep working even if the realtime server is offline.
        }
    }
}
