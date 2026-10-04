<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Enums;

enum AppointmentStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case CANCELLED = 'CANCELLED';
}
