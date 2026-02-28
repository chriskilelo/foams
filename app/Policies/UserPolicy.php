<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only admins can manage users. All other roles are denied.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }

    public function deactivate(User $user, User $model): bool
    {
        return false;
    }
}
