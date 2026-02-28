<?php

namespace App\Http\Middleware;

use App\Models\Asset;
use App\Models\Issue;
use App\Models\Scopes\RegionScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegionScopeMiddleware
{
    /**
     * Apply region-level data scoping for field officers.
     *
     * RICTO, ICTO, and AICTO are region-scoped — they may only see assets and
     * issues belonging to counties in their assigned region.  Director, NOC,
     * and admin roles retain full national visibility.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasAnyRole(['ricto', 'icto', 'aicto']) && $user->region_id !== null) {
            $regionId = $user->region_id;

            app()->instance('current_region_id', $regionId);

            $scope = new RegionScope($regionId);

            Asset::addGlobalScope($scope);
            Issue::addGlobalScope($scope);
        }

        return $next($request);
    }
}
