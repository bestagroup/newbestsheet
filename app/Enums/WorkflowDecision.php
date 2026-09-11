<?php

namespace App\Enums;

enum WorkflowDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
