<?php

declare(strict_types=1);

namespace App\Domains\Encounter\Enums;

enum EncounterType: string
{
    case OUTPATIENT = 'OUTPATIENT';
    case EMERGENCY = 'EMERGENCY';
    case INPATIENT = 'INPATIENT';
    case VIRTUAL = 'VIRTUAL';
    case OTHER = 'OTHER';
}
