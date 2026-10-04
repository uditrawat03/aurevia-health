<?php

declare(strict_types=1);

namespace App\Domains\Clinical\Enums;

enum ClinicalNoteAmendmentType: string
{
    case ADDENDUM = 'ADDENDUM';
    case CORRECTION = 'CORRECTION';
}
