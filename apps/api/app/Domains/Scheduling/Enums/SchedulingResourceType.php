<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Enums;

enum SchedulingResourceType: string
{
    case PROVIDER = 'PROVIDER';
    case ROOM = 'ROOM';
    case EQUIPMENT = 'EQUIPMENT';
}
