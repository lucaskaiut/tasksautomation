<?php

namespace App\Support\Enums;

enum TaskExecutionStatus: string
{
    case Claimed = 'claimed';
    case Running = 'running';
    case Review = 'review';
    case Done = 'done';
    case Failed = 'failed';
    case Blocked = 'blocked';
    case Cancelled = 'cancelled';
}
