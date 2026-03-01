<?php

namespace App\Services;

use App\Enums\AssetLogStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\Region;
use App\Models\Scopes\RegionScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UptimeService
{
    /**
     * Compute the uptime percentage for an asset over the given date range.
     *
     * Operational days = count of distinct logged_dates where any log has
     * status='operational'.  Days with no log are not counted as down — they
     * are simply absent from the numerator while still present in the
     * denominator (total calendar days in the range).
     *
     * Returns a float percentage, e.g. 95.33.
     */
    public function computeUptime(Asset $asset, Carbon $from, Carbon $to): float
    {
        if ($from->isAfter($to)) {
            return 0.0;
        }

        $totalDays = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        $operationalDays = AssetStatusLog::query()
            ->where('asset_id', $asset->id)
            ->where('status', AssetLogStatus::Operational->value)
            ->whereBetween('logged_date', [$from->toDateString(), $to->toDateString()])
            ->pluck('logged_date')
            ->unique()
            ->count();

        return round($operationalDays / $totalDays * 100, 2);
    }

    /**
     * Build a per-day availability calendar for the last $days days (inclusive of today).
     *
     * Each entry contains:
     *   - 'date'   => 'YYYY-MM-DD'
     *   - 'status' => 'operational'|'degraded'|'down'|'maintenance'|'no_log'
     *
     * When multiple logs exist for a day (multiple officers or an amendment),
     * the worst observed status is used: down > maintenance > degraded > operational.
     *
     * @return array<int, array{date: string, status: string}>
     */
    public function getAvailabilityCalendar(Asset $asset, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $logsByDate = AssetStatusLog::query()
            ->where('asset_id', $asset->id)
            ->whereBetween('logged_date', [$from->toDateString(), $to->toDateString()])
            ->get(['logged_date', 'status'])
            ->groupBy(fn ($log) => $log->logged_date instanceof Carbon
                ? $log->logged_date->toDateString()
                : substr((string) $log->logged_date, 0, 10)
            );

        $calendar = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $dateStr = $current->toDateString();

            $status = isset($logsByDate[$dateStr])
                ? $this->effectiveDayStatus($logsByDate[$dateStr])
                : 'no_log';

            $calendar[] = ['date' => $dateStr, 'status' => $status];
            $current->addDay();
        }

        return $calendar;
    }

    /**
     * Return per-asset-type average uptime for a region over the last $days days.
     *
     * Keys are AssetType string values ('wifi_hotspot', 'nofbi_node', 'ogn_equipment').
     * Values are percentage floats rounded to 2 decimal places, or null if no
     * assets of that type exist in the region.
     *
     * This method bypasses any active RegionScope global scope so that the
     * caller controls which region is queried.
     *
     * @return array<string, float|null>
     */
    public function getRegionUptimeSummary(Region $region, int $days = 30): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $assets = Asset::withoutGlobalScope(RegionScope::class)
            ->whereHas('county', fn ($q) => $q->where('region_id', $region->id))
            ->get(['id', 'type']);

        $summary = [];

        foreach (AssetType::cases() as $type) {
            $typeAssets = $assets->filter(fn (Asset $a) => $a->type === $type);

            if ($typeAssets->isEmpty()) {
                $summary[$type->value] = null;

                continue;
            }

            $assetIds = $typeAssets->pluck('id');

            $operationalDaysByAsset = AssetStatusLog::query()
                ->whereIn('asset_id', $assetIds)
                ->where('status', AssetLogStatus::Operational->value)
                ->whereBetween('logged_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('asset_id, COUNT(DISTINCT logged_date) as operational_days')
                ->groupBy('asset_id')
                ->pluck('operational_days', 'asset_id');

            $totalOperational = $assetIds->sum(fn ($id) => (int) $operationalDaysByAsset->get($id, 0));
            $avg = $totalOperational / ($typeAssets->count() * $days) * 100;

            $summary[$type->value] = round((float) $avg, 2);
        }

        return $summary;
    }

    /**
     * Pick the worst status from a collection of log rows for a single day.
     *
     * Priority (worst first): down > maintenance > degraded > operational
     *
     * @param  Collection<int, AssetStatusLog>  $logs
     */
    private function effectiveDayStatus(Collection $logs): string
    {
        $statuses = $logs->map(fn ($log) => $log->status instanceof AssetLogStatus
            ? $log->status->value
            : (string) $log->status
        )->toArray();

        foreach (['down', 'maintenance', 'degraded', 'operational'] as $priority) {
            if (in_array($priority, $statuses, true)) {
                return $priority;
            }
        }

        return 'no_log';
    }
}
