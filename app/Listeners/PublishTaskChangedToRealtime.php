<?php

namespace App\Listeners;

use App\Events\TaskChanged;
use App\Services\Realtime\TaskStreamPublisher;
use App\Support\Realtime\TaskStreamPayloadFactory;

class PublishTaskChangedToRealtime
{
    public function __construct(
        private readonly TaskStreamPayloadFactory $payloadFactory,
        private readonly TaskStreamPublisher $publisher,
    ) {}

    public function handle(TaskChanged $event): void
    {
        $this->publisher->publish(
            $this->payloadFactory->make(
                type: $event->type,
                task: $event->task,
                previous: $event->previous,
                changedAttributes: $event->changedAttributes,
            ),
        );
    }
}
