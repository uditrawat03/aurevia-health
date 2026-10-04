<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Enums;

enum ConsentDataCategory: string
{
    case DEMOGRAPHICS = 'DEMOGRAPHICS';
    case CLINICAL = 'CLINICAL';
    case BILLING = 'BILLING';
    case RESEARCH = 'RESEARCH';
}
