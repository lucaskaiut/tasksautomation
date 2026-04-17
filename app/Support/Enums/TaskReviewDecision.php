<?php

namespace App\Support\Enums;

enum TaskReviewDecision: string
{
    case Approved = 'approved';
    case NeedsAdjustment = 'needs_adjustment';
}
