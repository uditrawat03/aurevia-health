<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Enums;

enum ConsentPurpose: string
{
    case TREATMENT = 'TREATMENT';
    case CARE_COORDINATION = 'CARE_COORDINATION';
    case OPERATIONS = 'OPERATIONS';
    case BILLING = 'BILLING';
    case RESEARCH = 'RESEARCH';
}
