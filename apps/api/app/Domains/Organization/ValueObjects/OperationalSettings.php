<?php

declare(strict_types=1);

namespace App\Domains\Organization\ValueObjects;

use InvalidArgumentException;

final readonly class OperationalSettings
{
    public function __construct(
        public string $locale,
        public string $timezone,
        public string $weekStartsOn,
    ) {
        new OperationalSettingsOverride($locale, $timezone, $weekStartsOn);

        $hasMissingValue = $locale === '' || $timezone === '' || $weekStartsOn === '';
        if ($hasMissingValue) {
            throw new InvalidArgumentException('Resolved operational settings cannot contain empty values.');
        }
    }
}
