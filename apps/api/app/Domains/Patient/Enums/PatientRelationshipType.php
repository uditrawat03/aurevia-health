<?php

declare(strict_types=1);

namespace App\Domains\Patient\Enums;

enum PatientRelationshipType: string
{
    case PARENT = 'PARENT';
    case GUARDIAN = 'GUARDIAN';
    case SPOUSE = 'SPOUSE';
    case CHILD = 'CHILD';
    case SIBLING = 'SIBLING';
    case OTHER = 'OTHER';
}
