<?php

declare(strict_types=1);

namespace App\CountryProfiles;

use App\Domains\Organization\ValueObjects\CountryCode;

interface CountryProfileResolver
{
    public function current(CountryCode $countryCode): CountryProfileDefinition;

    public function pinned(
        CountryCode $countryCode,
        string $profileCode,
        string $version,
    ): CountryProfileDefinition;
}
