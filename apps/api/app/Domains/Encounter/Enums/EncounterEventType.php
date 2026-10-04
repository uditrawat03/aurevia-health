<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Enums;

enum EncounterEventType: string
{
    case CREATED = 'CREATED';
    case ARRIVED = 'ARRIVED';
    case STARTED = 'STARTED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
