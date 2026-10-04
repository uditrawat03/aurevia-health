<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum OrganizationRole: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case CLINICIAN = 'CLINICIAN';
    case STAFF = 'STAFF';
    case VIEWER = 'VIEWER';
}
