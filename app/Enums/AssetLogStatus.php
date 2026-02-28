<?php

namespace App\Enums;

enum AssetLogStatus: string
{
    case Operational = 'operational';
    case Degraded = 'degraded';
    case Down = 'down';
    case Maintenance = 'maintenance';
}
