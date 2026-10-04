<?php

declare(strict_types=1);

namespace App\Domains\Organization\Enums;

enum OperationalSettingKey: string
{
    case LOCALE = 'locale';
    case TIMEZONE = 'timezone';
    case WEEK_STARTS_ON = 'weekStartsOn';
}
