<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    /**
     * Admins bypass all individual checks and get full access.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * All authenticated field officers, NOC, and directors can browse issues.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['director', 'noc', 'ricto', 'icto', 'aicto', 'public_servant']);
    }

    /**
     * Anyone who can list issues can view a single issue.
     */
    public function view(User $user, Issue $issue): bool
    {
        return $user->hasAnyRole(['director', 'noc', 'ricto', 'icto', 'aicto', 'public_servant']);
    }

    /**
     * All authenticated roles can create issues (public submissions go via a separate
     * unauthenticated endpoint; this covers authenticated creation).
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['director', 'noc', 'ricto', 'icto', 'aicto', 'public_servant']);
    }

    /**
     * NOC and director can transition any issue.
     * RICTO can transition issues in their region (enforced at data layer).
     * ICTO can transition issues they created or are assigned to.
     */
    public function updateStatus(User $user, Issue $issue): bool
    {
        if ($user->hasAnyRole(['noc', 'director'])) {
            return true;
        }

        if ($user->hasRole('ricto')) {
            return true;
        }

        if ($user->hasRole('icto')) {
            return $issue->created_by_user_id === $user->id
                || $issue->assigned_to_user_id === $user->id;
        }

        return false;
    }

    /**
     * NOC, RICTO, and ICTO can escalate issues.
     */
    public function escalate(User $user, Issue $issue): bool
    {
        return $user->hasAnyRole(['noc', 'ricto', 'icto']);
    }

    /**
     * NOC, RICTO, and ICTO (assignee or creator) can resolve issues.
     */
    public function resolve(User $user, Issue $issue): bool
    {
        if ($user->hasRole('noc')) {
            return true;
        }

        if ($user->hasRole('ricto')) {
            return true;
        }

        if ($user->hasRole('icto')) {
            return $issue->created_by_user_id === $user->id
                || $issue->assigned_to_user_id === $user->id;
        }

        return false;
    }

    /**
     * NOC and director can close resolved issues.
     */
    public function close(User $user, Issue $issue): bool
    {
        return $user->hasAnyRole(['noc', 'director', 'ricto']);
    }

    /**
     * Issues are never hard-deleted.
     */
    public function delete(User $user, Issue $issue): bool
    {
        return false;
    }

    public function restore(User $user, Issue $issue): bool
    {
        return false;
    }

    public function forceDelete(User $user, Issue $issue): bool
    {
        return false;
    }
}
