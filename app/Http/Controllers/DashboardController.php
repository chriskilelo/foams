<?php

namespace App\Http\Controllers;

use App\Enums\AssetLogStatus;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\Issue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the main dashboard with region-scoped asset and issue statistics.
     *
     * Asset queries respect the RegionScope global scope applied by
     * RegionScopeMiddleware for RICTO / ICTO / AICTO users.
     * Admin, Director, and NOC see national figures.
     */
    public function __invoke(Request $request): Response
    {
        $today = now()->toDateString();
        $thirtyDaysAgo = now()->subDays(29)->toDateString();

        // Asset counts — each Asset::query() call picks up the active global scope
        $assetsTotal = Asset::query()->count();
        $assetsOnline = Asset::query()->where('status', AssetStatus::Operational)->count();
        $assetsDegraded = Asset::query()->where('status', AssetStatus::Degraded)->count();
        $assetsDown = Asset::query()->where('status', AssetStatus::Down)->count();

        // Average 30-day uptime across all (region-scoped) assets — 2 queries
        $assetIds = Asset::query()->pluck('id');
        $avgUptime30d = 0.0;

        if ($assetIds->isNotEmpty()) {
            $operationalDaysByAsset = AssetStatusLog::query()
                ->whereIn('asset_id', $assetIds)
                ->where('status', AssetLogStatus::Operational->value)
                ->whereBetween('logged_date', [$thirtyDaysAgo, $today])
                ->selectRaw('asset_id, COUNT(DISTINCT logged_date) as operational_days')
                ->groupBy('asset_id')
                ->pluck('operational_days', 'asset_id');

            $totalOperational = $operationalDaysByAsset->sum();
            $avgUptime30d = round($totalOperational / ($assetIds->count() * 30) * 100, 1);
        }

        // Open issues — Issue model is also region-scoped for field officers
        $openIssues = Issue::query()
            ->whereNotIn('status', [
                'resolved',
                'closed',
                'duplicate',
            ])
            ->count();

        // Status logs submitted today in the current scope.
        // whereHas('asset') leverages the active RegionScope on the Asset model
        // so field officers only see logs for their region's assets.
        $logsToday = AssetStatusLog::query()
            ->whereDate('logged_date', $today)
            ->whereHas('asset')
            ->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'assets_total' => $assetsTotal,
                'assets_online' => $assetsOnline,
                'assets_degraded' => $assetsDegraded,
                'assets_down' => $assetsDown,
                'avg_uptime_30d' => $avgUptime30d,
                'open_issues' => $openIssues,
                'logs_today' => $logsToday,
            ],
        ]);
    }
}
