<?php

declare(strict_types=1);

namespace App\CountryProfiles;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class CountryProfileDefinition
{
    public function __construct(
        public string $countryCode,
        public string $profileCode,
        public string $version,
        public OperationalSettingsOverride $defaults,
    ) {}
}
