<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class TaskChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public const Created = 'task.created';

    public const Updated = 'task.updated';

    public const Deleted = 'task.deleted';

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<int, string>  $changedAttributes
     */
    public function __construct(
        public readonly string $type,
        public readonly Task $task,
        public readonly array $previous = [],
        public readonly array $changedAttributes = [],
    ) {}
}
