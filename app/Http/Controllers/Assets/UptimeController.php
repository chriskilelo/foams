<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\UptimeService;
use Inertia\Inertia;
use Inertia\Response;

class UptimeController extends Controller
{
    public function __construct(private readonly UptimeService $uptimeService) {}

    /**
     * Display the 30-day availability calendar for a single asset.
     */
    public function show(Asset $asset): Response
    {
        $this->authorize('view', $asset);

        $asset->load(['county.region']);

        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $uptimePercent = $this->uptimeService->computeUptime($asset, $from, $to);
        $calendar = $this->uptimeService->getAvailabilityCalendar($asset);

        return Inertia::render('Assets/Uptime', [
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'type' => $asset->type->value,
                'status' => $asset->status->value,
                'county' => [
                    'name' => $asset->county->name,
                    'region' => ['name' => $asset->county->region->name],
                ],
            ],
            'uptime_percent' => $uptimePercent,
            'calendar' => $calendar,
        ]);
    }
}
