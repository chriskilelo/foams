<?php

namespace App\Policies;

use App\Models\SlaConfiguration;
use App\Models\User;

class SlaConfigurationPolicy
{
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

    public function update(User $user, SlaConfiguration $slaConfiguration): bool
    {
        return false;
    }
}
