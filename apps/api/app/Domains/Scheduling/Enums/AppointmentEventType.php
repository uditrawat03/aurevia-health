<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Enums;

enum AppointmentEventType: string
{
    case BOOKED = 'BOOKED';
    case RESCHEDULED = 'RESCHEDULED';
    case CANCELLED = 'CANCELLED';
}
