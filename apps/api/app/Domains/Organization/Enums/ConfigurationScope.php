<?php

declare(strict_types=1);

namespace App\Domains\Organization\Enums;

enum ConfigurationScope: string
{
    case ORGANIZATION = 'ORGANIZATION';
    case FACILITY = 'FACILITY';
    case DEPARTMENT = 'DEPARTMENT';
}
