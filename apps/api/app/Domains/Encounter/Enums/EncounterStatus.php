<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Enums;

enum EncounterStatus: string
{
    case PLANNED = 'PLANNED';
    case ARRIVED = 'ARRIVED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
