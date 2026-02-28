<?php

namespace App\Enums;

enum AssetType: string
{
    case WifiHotspot = 'wifi_hotspot';
    case NofbiNode = 'nofbi_node';
    case OgnEquipment = 'ogn_equipment';
}
