<?php

declare(strict_types=1);

namespace App\CountryProfiles;

use App\Domains\Organization\Enums\WeekStart;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class UnitedStatesCountryProfile implements CountryHealthcareProfile
{
    public const string CODE = 'US';
    public const string VERSION = '1.0.0';
    public const string DEFAULT_LOCALE = 'en-US';

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
        // The United States spans multiple timezones, so timezone remains organization/facility controlled.
        return new OperationalSettingsOverride(self::DEFAULT_LOCALE, null, WeekStart::SUNDAY->value);
    }
}
