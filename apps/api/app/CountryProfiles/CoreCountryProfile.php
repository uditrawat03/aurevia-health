<?php

declare(strict_types=1);

namespace App\CountryProfiles;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class CoreCountryProfile implements CountryHealthcareProfile
{
    public const string CODE = 'CORE';
    public const string VERSION = '1.0.0';

    public function code(): string
    {
        return self::CODE;
    }

    public function version(): string
    {
        return self::VERSION;
    }

    public function defaults(): OperationalSettingsOverride
    {
        return new OperationalSettingsOverride(null, null, null);
    }
}
