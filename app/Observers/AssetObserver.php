<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\County;
use App\Services\AssetService;

class AssetObserver
{
    public function __construct(private readonly AssetService $assetService) {}

    /**
     * Auto-generate the asset_code before the asset is persisted.
     *
     * If the code is already set (e.g. from a factory or import), it is left
     * unchanged so tests and seeders can supply deterministic codes.
     */
    public function creating(Asset $asset): void
    {
        if (empty($asset->asset_code)) {
            $county = County::findOrFail($asset->county_id);
            $asset->asset_code = $this->assetService->generateAssetCode($asset->type, $county);
        }
    }
}
