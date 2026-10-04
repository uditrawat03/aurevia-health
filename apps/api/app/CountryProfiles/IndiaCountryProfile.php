<?php

declare(strict_types=1);

namespace App\CountryProfiles;

use App\Domains\Organization\Enums\WeekStart;
use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

final readonly class IndiaCountryProfile implements CountryHealthcareProfile
{
    public const string CODE = 'IN';
    public const string VERSION = '1.0.0';
    public const string DEFAULT_LOCALE = 'en-IN';
    public const string DEFAULT_TIMEZONE = 'Asia/Kolkata';

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
        return new OperationalSettingsOverride(self::DEFAULT_LOCALE, self::DEFAULT_TIMEZONE, WeekStart::MONDAY->value);
    }
}
