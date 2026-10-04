<?php

declare(strict_types=1);

namespace App\CountryProfiles;

use App\Domains\Organization\ValueObjects\OperationalSettingsOverride;

interface CountryHealthcareProfile
{
    public function code(): string;

    public function version(): string;

    public function defaults(): OperationalSettingsOverride;
}
