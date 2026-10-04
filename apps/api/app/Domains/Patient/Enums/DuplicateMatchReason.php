<?php

declare(strict_types=1);

namespace App\Domains\Patient\Enums;

enum DuplicateMatchReason: string
{
    case EXACT_IDENTIFIER = 'EXACT_IDENTIFIER';
    case NAME_AND_DOB = 'NAME_AND_DOB';
    case FAMILY_NAME_AND_DOB = 'FAMILY_NAME_AND_DOB';
}
