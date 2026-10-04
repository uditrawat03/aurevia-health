<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Enums;

enum ConsentRecipientClass: string
{
    case CARE_TEAM = 'CARE_TEAM';
    case ORGANIZATION_STAFF = 'ORGANIZATION_STAFF';
    case EXTERNAL_PROVIDER = 'EXTERNAL_PROVIDER';
    case RESEARCH_TEAM = 'RESEARCH_TEAM';
}
