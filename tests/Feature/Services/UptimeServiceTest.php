<?php

use App\Enums\AssetLogStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\County;
use App\Models\Region;
use App\Models\User;
use App\Services\UptimeService;

// ─── computeUptime ────────────────────────────────────────────────────────────

describe('computeUptime', function () {
    it('returns 100% when all 30 days have operational logs', function () {
        $asset = Asset::factory()->create();
        $user = User::factory()->create();

        for ($i = 0; $i < 30; $i++) {
            AssetStatusLog::factory()->create([
                'asset_id' => $asset->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);
        }

        $service = new UptimeService;
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        expect($service->computeUptime($asset, $from, $to))->toBe(100.0);
    });

    it('returns 50% when 15 days are operational and 15 days are down', function () {
        $asset = Asset::factory()->create();
        $user = User::factory()->create();

        for ($i = 0; $i < 15; $i++) {
            AssetStatusLog::factory()->create([
                'asset_id' => $asset->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);
        }

        for ($i = 15; $i < 30; $i++) {
            AssetStatusLog::factory()->create([
                'asset_id' => $asset->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Down,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);
        }

        $service = new UptimeService;
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        expect($service->computeUptime($asset, $from, $to))->toBe(50.0);
    });

    it('treats days with no log as no_log — not counted as down — giving correct uptime', function () {
        $asset = Asset::factory()->create();
        $user = User::factory()->create();

        // 20 operational days — the remaining 10 days have no logs at all
        for ($i = 0; $i < 20; $i++) {
            AssetStatusLog::factory()->create([
                'asset_id' => $asset->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);
        }

        $service = new UptimeService;
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        // 20 operational / 30 total days = 66.67%
        // No-log days count in the denominator but not the numerator
        expect($service->computeUptime($asset, $from, $to))->toBe(66.67);
    });

    it('returns 0% for an asset with no logs', function () {
        $asset = Asset::factory()->create();

        $service = new UptimeService;
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        expect($service->computeUptime($asset, $from, $to))->toBe(0.0);
    });
});

// ─── getAvailabilityCalendar ──────────────────────────────────────────────────

describe('getAvailabilityCalendar', function () {
    it('returns 30 entries spanning the last 30 days', function () {
        $asset = Asset::factory()->create();

        $service = new UptimeService;
        $calendar = $service->getAvailabilityCalendar($asset, 30);

        expect($calendar)->toHaveCount(30);
        expect($calendar[0]['date'])->toBe(now()->subDays(29)->toDateString());
        expect($calendar[29]['date'])->toBe(now()->toDateString());
    });

    it('marks days with no log as no_log', function () {
        $asset = Asset::factory()->create();

        $service = new UptimeService;
        $calendar = $service->getAvailabilityCalendar($asset, 30);

        foreach ($calendar as $day) {
            expect($day['status'])->toBe('no_log');
        }
    });

    it('shows the correct status for logged days', function () {
        $asset = Asset::factory()->create();
        $user = User::factory()->create();

        AssetStatusLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $user->id,
            'status' => AssetLogStatus::Degraded,
            'logged_date' => now()->subDays(2)->toDateString(),
            'is_amendment' => false,
        ]);

        $service = new UptimeService;
        $calendar = $service->getAvailabilityCalendar($asset, 30);

        // Find the entry for 2 days ago
        $entry = collect($calendar)->firstWhere('date', now()->subDays(2)->toDateString());
        expect($entry['status'])->toBe('degraded');
    });

    it('uses worst status when multiple logs exist for the same day', function () {
        $asset = Asset::factory()->create();

        // Two officers log the same asset on the same day: one operational, one down
        AssetStatusLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => User::factory()->create()->id,
            'status' => AssetLogStatus::Operational,
            'logged_date' => now()->toDateString(),
            'is_amendment' => false,
        ]);

        AssetStatusLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => User::factory()->create()->id,
            'status' => AssetLogStatus::Down,
            'logged_date' => now()->toDateString(),
            'is_amendment' => false,
        ]);

        $service = new UptimeService;
        $calendar = $service->getAvailabilityCalendar($asset, 1);

        expect($calendar[0]['status'])->toBe('down');
    });
});

// ─── getRegionUptimeSummary ────────────────────────────────────────────────────

describe('getRegionUptimeSummary', function () {
    it('returns correct per-type average uptime for a region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        // Two WiFi assets: 30 and 15 operational days respectively
        $wifi1 = Asset::factory()->for($county, 'county')->create(['type' => AssetType::WifiHotspot]);
        $wifi2 = Asset::factory()->for($county, 'county')->create(['type' => AssetType::WifiHotspot]);

        // One NOFBI asset: 30 operational days
        $nofbi = Asset::factory()->for($county, 'county')->create(['type' => AssetType::NofbiNode]);

        $user = User::factory()->create();

        for ($i = 0; $i < 30; $i++) {
            AssetStatusLog::factory()->create([
                'asset_id' => $wifi1->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);

            AssetStatusLog::factory()->create([
                'asset_id' => $nofbi->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);
        }

        for ($i = 0; $i < 15; $i++) {
            AssetStatusLog::factory()->create([
                'asset_id' => $wifi2->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);
        }

        $service = new UptimeService;
        $summary = $service->getRegionUptimeSummary($region, 30);

        // WiFi: (30 + 15) / (2 × 30) × 100 = 75%
        expect($summary['wifi_hotspot'])->toBe(75.0);

        // NOFBI: 30 / (1 × 30) × 100 = 100%
        expect($summary['nofbi_node'])->toBe(100.0);

        // OGN: no assets → null
        expect($summary['ogn_equipment'])->toBeNull();
    });

    it('excludes assets from other regions', function () {
        $regionA = Region::factory()->create();
        $regionB = Region::factory()->create();

        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        $assetInA = Asset::factory()->for($countyA, 'county')->create(['type' => AssetType::WifiHotspot]);
        $assetInB = Asset::factory()->for($countyB, 'county')->create(['type' => AssetType::WifiHotspot]);

        $user = User::factory()->create();

        // Both assets have operational logs, but the summary for region B
        // should only count assetInB
        for ($i = 0; $i < 30; $i++) {
            AssetStatusLog::factory()->create([
                'asset_id' => $assetInA->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);

            AssetStatusLog::factory()->create([
                'asset_id' => $assetInB->id,
                'user_id' => $user->id,
                'status' => AssetLogStatus::Operational,
                'logged_date' => now()->subDays($i)->toDateString(),
                'is_amendment' => false,
            ]);
        }

        $service = new UptimeService;
        $summary = $service->getRegionUptimeSummary($regionB, 30);

        // Only assetInB counted → 100%
        expect($summary['wifi_hotspot'])->toBe(100.0);
    });
});
