<?php

declare(strict_types=1);

namespace App\Domains\Organization\Data;

final readonly class ResolvedOperationalSettingsData
{
    public function __construct(
        public string $locale,
        public string $timezone,
        public string $weekStartsOn,
        public string $localeSource,
        public string $timezoneSource,
        public string $weekStartsOnSource,
        public CountryProfileMetadataData $countryProfile,
    ) {}
}
