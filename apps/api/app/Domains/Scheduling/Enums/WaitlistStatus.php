<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Enums;

enum WaitlistStatus: string
{
    case WAITING = 'WAITING';
    case OFFERED = 'OFFERED';
    case BOOKED = 'BOOKED';
    case CANCELLED = 'CANCELLED';
}
