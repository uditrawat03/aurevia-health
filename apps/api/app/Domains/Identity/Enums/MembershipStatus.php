<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum MembershipStatus: string
{
    case ACTIVE = 'ACTIVE';
    case REVOKED = 'REVOKED';
}
