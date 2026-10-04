<?php

declare(strict_types=1);

use App\Domains\Organization\Enums\WeekStart;

return [
    'defaults' => [
        'locale' => 'en',
        'timezone' => 'UTC',
        'week_starts_on' => WeekStart::MONDAY->value,
    ],
];
