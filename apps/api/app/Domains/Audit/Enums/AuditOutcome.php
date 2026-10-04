<?php

declare(strict_types=1);

namespace App\Domains\Audit\Enums;

enum AuditOutcome: string
{
    case ALLOWED = 'ALLOWED';
    case DENIED = 'DENIED';
}
