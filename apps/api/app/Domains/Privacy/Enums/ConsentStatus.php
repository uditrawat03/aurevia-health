<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Enums;

enum ConsentStatus: string
{
    case ACTIVE = 'ACTIVE';
    case REVOKED = 'REVOKED';
}
