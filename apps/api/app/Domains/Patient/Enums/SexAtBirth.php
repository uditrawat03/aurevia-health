<?php

declare(strict_types=1);

namespace App\Domains\Patient\Enums;

enum SexAtBirth: string
{
    case FEMALE = 'FEMALE';
    case MALE = 'MALE';
    case INTERSEX = 'INTERSEX';
    case UNKNOWN = 'UNKNOWN';
}
