<?php

declare(strict_types=1);

namespace App\Enums;

enum CompletionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ManualReview = 'manual_review';
}
