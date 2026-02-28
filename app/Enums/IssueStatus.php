<?php

namespace App\Enums;

enum IssueStatus: string
{
    case New = 'new';
    case Acknowledged = 'acknowledged';
    case InProgress = 'in_progress';
    case PendingThirdParty = 'pending_third_party';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Duplicate = 'duplicate';
}
