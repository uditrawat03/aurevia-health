<?php

declare(strict_types=1);

namespace App\Domains\Patient\Enums;

enum PatientIdentifierType: string
{
    case MRN = 'MRN';
    case NATIONAL = 'NATIONAL';
    case INSURANCE = 'INSURANCE';
    case OTHER = 'OTHER';
}
