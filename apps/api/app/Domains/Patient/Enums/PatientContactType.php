<?php

declare(strict_types=1);

namespace App\Domains\Patient\Enums;

enum PatientContactType: string
{
    case PHONE = 'PHONE';
    case EMAIL = 'EMAIL';
}
