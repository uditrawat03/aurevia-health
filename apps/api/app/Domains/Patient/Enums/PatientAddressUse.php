<?php

declare(strict_types=1);

namespace App\Domains\Patient\Enums;

enum PatientAddressUse: string
{
    case HOME = 'HOME';
    case WORK = 'WORK';
    case OTHER = 'OTHER';
}
