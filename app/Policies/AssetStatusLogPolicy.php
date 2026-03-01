<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\User;

class AssetStatusLogPolicy
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
     * ICTO and AICTO officers can log status for assets in their own region.
     *
     * Route model binding does not respect the RegionScope global scope, so we
     * enforce the region constraint explicitly here to prevent cross-region logging.
     */
    public function create(User $user, Asset $asset): bool
    {
        if (! $user->hasAnyRole(['icto', 'aicto'])) {
            return false;
        }

        if ($user->region_id === null) {
            return true;
        }

        return (int) $asset->county()->value('region_id') === (int) $user->region_id;
    }

    /**
     * Only the officer who created the log (or admin, via before()) may amend it.
     */
    public function amend(User $user, AssetStatusLog $log): bool
    {
        return $user->id === $log->user_id;
    }

    /**
     * Admin, director, NOC, RICTO, ICTO, and AICTO can browse logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['director', 'noc', 'ricto', 'icto', 'aicto']);
    }
}
