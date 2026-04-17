<?php

namespace App\Support\Enums;

enum TaskReviewStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case NeedsAdjustment = 'needs_adjustment';
}
