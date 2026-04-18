<?php

namespace App\Observers;

use App\Events\TaskChanged;
use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        TaskChanged::dispatch(
            type: TaskChanged::Created,
            task: $task->withoutRelations(),
        );
    }

    public function updated(Task $task): void
    {
        TaskChanged::dispatch(
            type: TaskChanged::Updated,
            task: $task->withoutRelations(),
            previous: $task->getPrevious(),
            changedAttributes: array_keys($task->getChanges()),
        );
    }

    public function deleted(Task $task): void
    {
        TaskChanged::dispatch(
            type: TaskChanged::Deleted,
            task: $task->withoutRelations(),
            previous: $task->getOriginal(),
        );
    }
}
