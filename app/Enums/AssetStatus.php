<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Operational = 'operational';
    case Degraded = 'degraded';
    case Down = 'down';
    case Maintenance = 'maintenance';
    case Decommissioned = 'decommissioned';
}
