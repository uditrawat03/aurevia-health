<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Enums;

enum AllergyVerificationStatus: string
{
    case UNVERIFIED = 'UNVERIFIED';
    case CONFIRMED = 'CONFIRMED';
    case REFUTED = 'REFUTED';
    case ENTERED_IN_ERROR = 'ENTERED_IN_ERROR';
}
