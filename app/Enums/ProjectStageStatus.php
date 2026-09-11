<?php

namespace App\Enums;

enum ProjectStageStatus: string
{
    case Locked = 'locked';
    case AwaitingAssignment = 'awaiting_assignment';
    case AwaitingDocuments = 'awaiting_documents';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected], true);
    }
}
