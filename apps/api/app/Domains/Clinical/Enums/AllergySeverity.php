<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Enums;

enum AllergySeverity: string
{
    case UNKNOWN = 'UNKNOWN';
    case MILD = 'MILD';
    case MODERATE = 'MODERATE';
    case SEVERE = 'SEVERE';
}
