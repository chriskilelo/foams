<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    /**
     * Admins bypass all individual checks and get full access.
     * All other roles fall through to the individual methods.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Director, NOC, and field officers can browse the asset list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['director', 'noc', 'ricto', 'icto', 'aicto']);
    }

    /**
     * Director, NOC, and field officers can view a single asset.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->hasAnyRole(['director', 'noc', 'ricto', 'icto', 'aicto']);
    }

    /**
     * Only admin can create assets (admin handled by before()).
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * RICTO can edit assets in their region (region scoping is enforced
     * at the data layer by RegionScopeMiddleware, not here).
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->hasRole('ricto');
    }

    /**
     * Admin and RICTO can assign an asset to an ICTO or AICTO officer.
     * Region-scoping for RICTO is enforced in AssignAssetRequest.
     */
    public function assign(User $user, Asset $asset): bool
    {
        return $user->hasRole('ricto');
    }

    /**
     * Only admin can soft-delete assets (admin handled by before()).
     */
    public function delete(User $user, Asset $asset): bool
    {
        return false;
    }

    public function restore(User $user, Asset $asset): bool
    {
        return false;
    }

    public function forceDelete(User $user, Asset $asset): bool
    {
        return false;
    }
}
