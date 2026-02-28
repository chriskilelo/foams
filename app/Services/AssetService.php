<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\County;

class AssetService
{
    /**
     * Generate a unique asset code for the given type and county.
     *
     * Format: {PREFIX}-{COUNTY_CODE}-{SEQUENCE}
     * e.g. WIFI-MSA-001, NOFBI-NBI-003, OGN-KSM-012
     *
     * The sequence is per county per type and is never reused (counts
     * soft-deleted assets too) so codes are permanently unique.
     */
    public function generateAssetCode(AssetType $type, County $county): string
    {
        $prefix = match ($type) {
            AssetType::WifiHotspot => 'WIFI',
            AssetType::NofbiNode => 'NOFBI',
            AssetType::OgnEquipment => 'OGN',
        };

        $sequence = Asset::withTrashed()
            ->where('county_id', $county->id)
            ->where('type', $type->value)
            ->count() + 1;

        return sprintf('%s-%s-%03d', $prefix, strtoupper($county->code), $sequence);
    }
}
