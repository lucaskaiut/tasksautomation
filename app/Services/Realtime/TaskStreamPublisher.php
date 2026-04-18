<?php

namespace App\Services\Realtime;

use Throwable;

class TaskStreamPublisher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function publish(array $payload): void
    {
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
        } catch (Throwable) {
            // The application must keep working even if the realtime server is offline.
        }
    }
}
