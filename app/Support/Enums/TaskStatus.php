<?php

namespace App\Support\Enums;

enum TaskStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Claimed = 'claimed';
    case Running = 'running';
    case Review = 'review';
    case NeedsAdjustment = 'needs_adjustment';
    case Done = 'done';
    case Failed = 'failed';
    case Blocked = 'blocked';
    case Cancelled = 'cancelled';
}

