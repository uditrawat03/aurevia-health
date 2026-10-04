<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Enums;

enum AllergyStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case RESOLVED = 'RESOLVED';
}
