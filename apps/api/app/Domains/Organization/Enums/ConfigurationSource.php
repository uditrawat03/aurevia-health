<?php

declare(strict_types=1);

namespace App\Domains\Organization\Enums;

enum ConfigurationSource: string
{
    case GLOBAL_DEFAULT = 'GLOBAL_DEFAULT';
    case COUNTRY_PROFILE = 'COUNTRY_PROFILE';
    case ORGANIZATION = 'ORGANIZATION';
    case FACILITY = 'FACILITY';
    case DEPARTMENT = 'DEPARTMENT';
}
